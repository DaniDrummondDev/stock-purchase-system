<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\AgentResult;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\AI\AiConfigResolver;
use Laravel\AI\Facades\Ai;

final class EducatorAgent implements FinanceAgentInterface
{
    private const GLOSSARY_TERMS = [
        'Preço Médio' => 'Média ponderada do preço de compra de um ativo, calculada considerando quantidade e valor de cada operação.',
        'Lote Padrão' => 'Quantidade mínima de ações negociada no mercado à vista (geralmente 100 unidades).',
        'Lote Fracionário' => 'Negociação de quantidades inferiores ao lote padrão (1 a 99 ações), identificado pelo sufixo F no ticker.',
        'Compra Programada' => 'Estratégia de investimento onde aportes são realizados automaticamente em datas fixas (dias 5, 15 e 25 do mês).',
        'Rebalanceamento' => 'Ajuste periódico da carteira para manter os percentuais de alocação alinhados à cesta ideal (Top Five).',
        'Custódia' => 'Registro de propriedade de ativos financeiros em nome do investidor, mantido pela corretora/B3.',
        'IR Dedo-Duro' => 'Imposto de renda retido na fonte (0,005%) sobre o valor de venda de ações, que serve como sinalização à Receita Federal.',
        'Cesta Top Five' => 'Carteira recomendada composta por 5 ações selecionadas com percentuais de alocação definidos.',
        'Dividendo' => 'Parcela do lucro de uma empresa distribuída aos acionistas, proporcional à quantidade de ações.',
        'Volatilidade' => 'Medida estatística da variação de preço de um ativo ao longo do tempo, indicador de risco.',
        'Selic' => 'Taxa básica de juros da economia brasileira, definida pelo Copom/Banco Central.',
        'IPCA' => 'Índice Nacional de Preços ao Consumidor Amplo, principal indicador de inflação no Brasil.',
        'B3' => 'Brasil, Bolsa, Balcão — a bolsa de valores oficial do Brasil, sediada em São Paulo.',
    ];

    public function __construct(
        private readonly CustodiaRepositoryInterface $custodiaRepo,
        private readonly CestaRepositoryInterface $cestaRepo,
        private readonly CotacaoRepositoryInterface $cotacaoRepo,
        private readonly AiConfigResolver $configResolver,
    ) {}

    public function getName(): string
    {
        return 'educator';
    }

    public function getDescription(): string
    {
        return 'Explica conceitos financeiros e de investimento de forma didática, utilizando exemplos concretos da carteira do cliente quando disponível.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['explain_concept'],
                    'description' => 'A ação a executar: explain_concept (explica um conceito financeiro)',
                ],
                'concept' => [
                    'type' => 'string',
                    'description' => 'O conceito financeiro a ser explicado (obrigatório)',
                ],
                'cliente_id' => [
                    'type' => 'string',
                    'description' => 'UUID do cliente (opcional — se informado, inclui exemplos da carteira do cliente)',
                ],
            ],
            'required' => ['action', 'concept'],
        ];
    }

    public function execute(AgentContext $context): AgentResult
    {
        $params = $context->additionalParams;
        $action = $params['action'] ?? 'explain_concept';
        $concept = $params['concept'] ?? throw new \InvalidArgumentException('concept é obrigatório');
        $clienteId = $params['cliente_id'] ?? $context->clienteId ?? null;

        $startTime = hrtime(true);

        try {
            $result = match ($action) {
                'explain_concept' => $this->explainConcept($concept, $clienteId),
                default => throw new \InvalidArgumentException("Ação desconhecida: {$action}"),
            };

            $executionMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            return new AgentResult(
                data: $result['data'],
                summary: $result['summary'],
                confidence: $result['confidence'],
                metadata: [
                    'action' => $action,
                    'concept' => $concept,
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
     * Explain a financial concept using LLM with optional portfolio context.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function explainConcept(string $concept, ?string $clienteId): array
    {
        $systemPrompt = $this->buildSystemPrompt($clienteId);
        $provider = $this->configResolver->resolveProviderName('llm', $clienteId);

        try {
            $response = Ai::agent()
                ->using($provider)
                ->withInstructions($systemPrompt)
                ->prompt($concept);

            $explanation = trim((string) $response);

            return [
                'data' => [
                    'concept' => $concept,
                    'explanation' => $explanation,
                    'glossaryMatch' => $this->findGlossaryMatch($concept),
                    'hasPortfolioContext' => $clienteId !== null,
                ],
                'summary' => $explanation,
                'confidence' => 0.8,
            ];
        } catch (\Throwable $e) {
            // Fallback to glossary if LLM fails
            $glossaryMatch = $this->findGlossaryMatch($concept);

            if ($glossaryMatch !== null) {
                return [
                    'data' => [
                        'concept' => $concept,
                        'explanation' => $glossaryMatch,
                        'glossaryMatch' => $glossaryMatch,
                        'hasPortfolioContext' => false,
                        'llmFallback' => true,
                    ],
                    'summary' => $glossaryMatch,
                    'confidence' => 0.6,
                ];
            }

            throw $e;
        }
    }

    /**
     * Build the system prompt with glossary and optional portfolio context.
     */
    private function buildSystemPrompt(?string $clienteId): string
    {
        $glossarySection = "## Glossário Financeiro\n";
        foreach (self::GLOSSARY_TERMS as $term => $definition) {
            $glossarySection .= "- **{$term}**: {$definition}\n";
        }

        $portfolioSection = '';

        if ($clienteId !== null) {
            $portfolioSection = $this->buildPortfolioContext($clienteId);
        }

        return <<<PROMPT
        Você é um educador financeiro especializado no mercado de ações brasileiro. Sua missão é explicar conceitos de forma clara, didática e acessível, usando linguagem simples em português.

        Regras:
        1. Sempre responda em português brasileiro.
        2. Use exemplos práticos e analogias quando possível.
        3. Se o conceito estiver no glossário abaixo, use a definição como base e expanda.
        4. Se houver dados da carteira do cliente, use-os como exemplos concretos.
        5. Mantenha a explicação concisa (máximo 3 parágrafos).
        6. Nunca forneça recomendações de compra ou venda de ativos específicos.

        {$glossarySection}

        {$portfolioSection}
        PROMPT;
    }

    /**
     * Build portfolio context section for the prompt.
     */
    private function buildPortfolioContext(string $clienteId): string
    {
        $custodias = $this->custodiaRepo->findByClienteId($clienteId);
        $cesta = $this->cestaRepo->findAtiva();

        if (empty($custodias) && $cesta === null) {
            return '';
        }

        $section = "## Contexto da Carteira do Cliente\n";

        if ($cesta !== null) {
            $percentuais = $cesta->percentualPorTicker();
            $section .= '**Cesta ativa (Top Five):** '.implode(', ', array_map(
                fn ($ticker, $pct) => "{$ticker} ({$pct}%)",
                array_keys($percentuais),
                array_values($percentuais),
            ))."\n";
        }

        if (! empty($custodias)) {
            $tickers = array_map(fn ($c) => $c->ticker(), $custodias);
            $cotacoes = $this->cotacaoRepo->findLatestByTickers($tickers);
            $cotacaoMap = [];
            foreach ($cotacoes as $cotacao) {
                $cotacaoMap[$cotacao->ticker()] = $cotacao->precoFechamento();
            }

            $section .= "**Posições do cliente:**\n";
            foreach ($custodias as $custodia) {
                $precoAtual = $cotacaoMap[$custodia->ticker()] ?? $custodia->precoMedio();
                $section .= "- {$custodia->ticker()}: {$custodia->quantidade()} ações, "
                    .'preço médio R$ '.number_format($custodia->precoMedio(), 2, ',', '.')
                    .', cotação atual R$ '.number_format($precoAtual, 2, ',', '.')."\n";
            }
        }

        return $section;
    }

    /**
     * Find a matching glossary term for the given concept.
     */
    private function findGlossaryMatch(string $concept): ?string
    {
        $lower = mb_strtolower($concept);

        foreach (self::GLOSSARY_TERMS as $term => $definition) {
            if (mb_stripos($lower, mb_strtolower($term)) !== false) {
                return "**{$term}**: {$definition}";
            }
        }

        return null;
    }
}
