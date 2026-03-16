<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\AgentResult;
use App\Domain\AI\Contracts\DataQuery;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Infrastructure\AI\AiConfigResolver;
use App\Infrastructure\AI\DataProviders\DataProviderManager;
use Laravel\AI\Facades\Ai;

final class MarketIntelligenceAgent implements FinanceAgentInterface
{
    public function __construct(
        private readonly DataProviderManager $dataProviderManager,
        private readonly CustodiaRepositoryInterface $custodiaRepo,
        private readonly AiConfigResolver $configResolver,
    ) {}

    public function getName(): string
    {
        return 'market_intelligence';
    }

    public function getDescription(): string
    {
        return 'Fornece contexto de mercado combinando cotações de ativos, indicadores macroeconômicos (Selic, IPCA, câmbio) e análise gerada por IA para suporte à tomada de decisão de investimentos.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['get_market_context'],
                    'description' => 'A ação a executar: get_market_context (contexto completo de mercado)',
                ],
                'cliente_id' => [
                    'type' => 'string',
                    'description' => 'UUID do cliente (opcional — se informado, carrega tickers da carteira)',
                ],
                'tickers' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Lista de tickers para consultar (opcional — usado quando cliente_id não é informado)',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(AgentContext $context): AgentResult
    {
        $params = $context->additionalParams;
        $action = $params['action'] ?? 'get_market_context';
        $clienteId = $params['cliente_id'] ?? $context->clienteId ?? null;
        $tickers = $params['tickers'] ?? [];

        $startTime = hrtime(true);

        try {
            $result = match ($action) {
                'get_market_context' => $this->getMarketContext($clienteId, $tickers),
                default => throw new \InvalidArgumentException("Ação desconhecida: {$action}"),
            };

            $executionMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            return new AgentResult(
                data: $result['data'],
                summary: $result['summary'],
                confidence: $result['confidence'],
                metadata: [
                    'action' => $action,
                    'execution_time_ms' => $executionMs,
                    'agent' => $this->getName(),
                ],
            );
        } catch (\Throwable $e) {
            $executionMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            return new AgentResult(
                data: ['error' => $e->getMessage()],
                summary: "Erro ao executar {$action}: {$e->getMessage()}",
                confidence: 0.0,
                metadata: [
                    'action' => $action,
                    'execution_time_ms' => $executionMs,
                    'error' => true,
                ],
            );
        }
    }

    /**
     * Build full market context combining portfolio data and macro indicators.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function getMarketContext(?string $clienteId, array $tickers): array
    {
        // 1. Resolve tickers from portfolio or use provided list
        if ($clienteId !== null && empty($tickers)) {
            $custodias = $this->custodiaRepo->findByClienteId($clienteId);
            $tickers = array_map(fn ($c) => $c->ticker(), $custodias);
        }

        // 2. Query quotation data for portfolio tickers
        $quotations = [];
        if (! empty($tickers)) {
            $quotationResult = $this->dataProviderManager->queryWithFallback(
                new DataQuery(
                    capability: 'quotation',
                    params: ['tickers' => $tickers],
                    cacheTtlSeconds: 300,
                ),
            );

            if ($quotationResult !== null) {
                $quotations = $quotationResult->data;
            }
        }

        // 3. Query macro indicators from BCB
        $interestRates = $this->queryMacro('interest_rates');
        $inflation = $this->queryMacro('inflation');
        $exchangeRates = $this->queryMacro('exchange_rates');

        // 4. Build context summary
        $contextData = [
            'tickers' => $tickers,
            'quotations' => $quotations,
            'macro' => [
                'interest_rates' => $interestRates,
                'inflation' => $inflation,
                'exchange_rates' => $exchangeRates,
            ],
        ];

        // 5. Generate LLM insight
        $llmSummary = $this->generateInsight($contextData, $clienteId);

        return [
            'data' => [
                'tickers' => $tickers,
                'quotations' => $quotations,
                'macro' => $contextData['macro'],
                'insight' => $llmSummary,
                'generatedAt' => now()->toIso8601String(),
            ],
            'summary' => $llmSummary,
            'confidence' => 0.75,
        ];
    }

    /**
     * Query a macro indicator capability with fallback.
     */
    private function queryMacro(string $capability): array
    {
        $result = $this->dataProviderManager->queryWithFallback(
            new DataQuery(
                capability: $capability,
                params: [],
                cacheTtlSeconds: 3600,
            ),
        );

        return $result?->data ?? [];
    }

    /**
     * Generate contextual insight using LLM.
     */
    private function generateInsight(array $contextData, ?string $clienteId): string
    {
        try {
            $provider = $this->configResolver->resolveProviderName('llm', $clienteId);

            $tickerList = implode(', ', $contextData['tickers']);
            $macroSummary = $this->buildMacroPromptSection($contextData['macro']);
            $quotationSummary = $this->buildQuotationPromptSection($contextData['quotations']);

            $prompt = <<<PROMPT
            Você é um analista de mercado financeiro brasileiro. Com base nos dados abaixo, forneça um resumo contextual conciso (máximo 3 parágrafos) em português sobre o cenário atual de mercado e como ele pode impactar a carteira do investidor.

            **Ativos na carteira:** {$tickerList}

            **Cotações recentes:**
            {$quotationSummary}

            **Indicadores macroeconômicos:**
            {$macroSummary}

            Foque em: tendências relevantes, riscos e oportunidades para os ativos listados.
            PROMPT;

            $response = Ai::agent()
                ->using($provider)
                ->prompt($prompt);

            return trim((string) $response);
        } catch (\Throwable $e) {
            return 'Não foi possível gerar análise contextual via IA: '.$e->getMessage();
        }
    }

    /**
     * Build macro section for the LLM prompt.
     */
    private function buildMacroPromptSection(array $macro): string
    {
        $lines = [];

        if (! empty($macro['interest_rates'])) {
            $selic = $macro['interest_rates']['selic'] ?? $macro['interest_rates']['value'] ?? 'N/D';
            $lines[] = "- Taxa Selic: {$selic}%";
        }

        if (! empty($macro['inflation'])) {
            $ipca = $macro['inflation']['ipca'] ?? $macro['inflation']['value'] ?? 'N/D';
            $lines[] = "- IPCA: {$ipca}%";
        }

        if (! empty($macro['exchange_rates'])) {
            $usd = $macro['exchange_rates']['USD'] ?? $macro['exchange_rates']['value'] ?? 'N/D';
            $lines[] = "- Dólar (USD/BRL): R$ {$usd}";
        }

        return empty($lines) ? 'Dados macroeconômicos indisponíveis.' : implode("\n", $lines);
    }

    /**
     * Build quotation section for the LLM prompt.
     */
    private function buildQuotationPromptSection(array $quotations): string
    {
        if (empty($quotations)) {
            return 'Cotações indisponíveis.';
        }

        $lines = [];
        foreach ($quotations as $ticker => $data) {
            if (is_array($data)) {
                $price = $data['price'] ?? $data['preco_fechamento'] ?? $data['close'] ?? 'N/D';
                $lines[] = "- {$ticker}: R$ {$price}";
            } else {
                $lines[] = "- {$ticker}: R$ {$data}";
            }
        }

        return implode("\n", $lines);
    }
}
