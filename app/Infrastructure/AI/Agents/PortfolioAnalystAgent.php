<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\AgentResult;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\AI\Contracts\RecommendationServiceInterface;
use App\Domain\AI\ValueObjects\PortfolioAnalysis;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;

final class PortfolioAnalystAgent implements FinanceAgentInterface
{
    public function __construct(
        private readonly CestaRepositoryInterface $cestaRepo,
        private readonly CustodiaRepositoryInterface $custodiaRepo,
        private readonly CotacaoRepositoryInterface $cotacaoRepo,
        private readonly RecommendationServiceInterface $recommendationService,
    ) {}

    public function getName(): string
    {
        return 'portfolio_analyst';
    }

    public function getDescription(): string
    {
        return 'Analisa composição da carteira do cliente, calcula desvios em relação à cesta ideal, estima P/L (lucro ou prejuízo) e sugere rebalanceamento da cesta Top Five usando IA com dados de mercado.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['analyze_composition', 'estimate_pl', 'recommend_basket'],
                    'description' => 'A ação a executar: analyze_composition (desvio carteira vs cesta ideal), estimate_pl (lucro/prejuízo da carteira), recommend_basket (sugestão IA de nova cesta)',
                ],
                'cliente_id' => [
                    'type' => 'string',
                    'description' => 'UUID do cliente (obrigatório para analyze_composition e estimate_pl)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Número de sugestões para recommend_basket (default: 5)',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(AgentContext $context): AgentResult
    {
        $params = $context->additionalParams;
        $action = $params['action'] ?? 'analyze_composition';
        $clienteId = $params['cliente_id'] ?? $context->clienteId;

        $startTime = hrtime(true);

        try {
            $result = match ($action) {
                'analyze_composition' => $this->analyzeComposition($clienteId),
                'estimate_pl' => $this->estimatePL($clienteId),
                'recommend_basket' => $this->recommendBasket($params['limit'] ?? 5),
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
     * Analyze portfolio composition vs target basket.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function analyzeComposition(string $clienteId): array
    {
        $cesta = $this->cestaRepo->findAtiva();
        $custodias = $this->custodiaRepo->findByClienteId($clienteId);

        if ($cesta === null) {
            return [
                'data' => [],
                'summary' => 'Não existe cesta ativa para comparação.',
                'confidence' => 0.0,
            ];
        }

        if (empty($custodias)) {
            return [
                'data' => ['target' => $cesta->percentualPorTicker()],
                'summary' => 'Cliente não possui custódia. A carteira está vazia.',
                'confidence' => 1.0,
            ];
        }

        $targetPercentuais = $cesta->percentualPorTicker();

        // Get current market values for each custodia
        $tickers = array_map(fn ($c) => $c->ticker(), $custodias);
        $cotacoes = $this->cotacaoRepo->findLatestByTickers($tickers);
        $cotacaoMap = [];
        foreach ($cotacoes as $cotacao) {
            $cotacaoMap[$cotacao->ticker()] = $cotacao->precoFechamento();
        }

        // Calculate actual composition
        $totalValue = 0.0;
        $positions = [];
        foreach ($custodias as $custodia) {
            $price = $cotacaoMap[$custodia->ticker()] ?? $custodia->precoMedio();
            $value = $price * $custodia->quantidade();
            $totalValue += $value;
            $positions[$custodia->ticker()] = $value;
        }

        $composition = [];
        $maxDeviation = 0.0;
        foreach ($targetPercentuais as $ticker => $target) {
            $actual = $totalValue > 0
                ? (($positions[$ticker] ?? 0) / $totalValue) * 100
                : 0.0;
            $deviation = round($actual - $target, 2);
            $maxDeviation = max($maxDeviation, abs($deviation));

            $composition[] = [
                'ticker' => $ticker,
                'targetPercentual' => $target,
                'actualPercentual' => round($actual, 2),
                'deviationPp' => $deviation,
            ];
        }

        // Check for tickers in custodia but not in basket
        foreach ($positions as $ticker => $value) {
            if (! isset($targetPercentuais[$ticker])) {
                $actual = $totalValue > 0 ? ($value / $totalValue) * 100 : 0.0;
                $composition[] = [
                    'ticker' => $ticker,
                    'targetPercentual' => 0.0,
                    'actualPercentual' => round($actual, 2),
                    'deviationPp' => round($actual, 2),
                ];
            }
        }

        $analysis = new PortfolioAnalysis(
            composition: $composition,
            estimatedPL: [],
            totalCusto: 0.0,
            totalValorAtual: $totalValue,
            totalPL: 0.0,
            totalPLPercentual: 0.0,
        );

        $deviationWarning = $maxDeviation > 5.0
            ? " Desvio máximo de {$maxDeviation}pp detectado — considerar rebalanceamento."
            : '';

        return [
            'data' => [
                'composition' => $analysis->composition,
                'totalValorAtual' => round($totalValue, 2),
            ],
            'summary' => "Carteira analisada com {$this->count($composition)} posições. Valor total: R\$ ".number_format($totalValue, 2, ',', '.').".{$deviationWarning}",
            'confidence' => 0.9,
        ];
    }

    /**
     * Estimate P/L for the client portfolio.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function estimatePL(string $clienteId): array
    {
        $custodias = $this->custodiaRepo->findByClienteId($clienteId);

        if (empty($custodias)) {
            return [
                'data' => [],
                'summary' => 'Cliente não possui custódia.',
                'confidence' => 1.0,
            ];
        }

        $tickers = array_map(fn ($c) => $c->ticker(), $custodias);
        $cotacoes = $this->cotacaoRepo->findLatestByTickers($tickers);
        $cotacaoMap = [];
        foreach ($cotacoes as $cotacao) {
            $cotacaoMap[$cotacao->ticker()] = $cotacao->precoFechamento();
        }

        $estimatedPL = [];
        $totalCusto = 0.0;
        $totalValorAtual = 0.0;

        foreach ($custodias as $custodia) {
            $precoAtual = $cotacaoMap[$custodia->ticker()] ?? $custodia->precoMedio();
            $custo = $custodia->precoMedio() * $custodia->quantidade();
            $valorAtual = $precoAtual * $custodia->quantidade();
            $pl = $valorAtual - $custo;
            $plPercent = $custo > 0 ? ($pl / $custo) * 100 : 0.0;

            $totalCusto += $custo;
            $totalValorAtual += $valorAtual;

            $estimatedPL[] = [
                'ticker' => $custodia->ticker(),
                'quantidade' => $custodia->quantidade(),
                'precoMedio' => round($custodia->precoMedio(), 2),
                'cotacaoAtual' => round($precoAtual, 2),
                'custoTotal' => round($custo, 2),
                'valorAtual' => round($valorAtual, 2),
                'lucroOuPrejuizo' => round($pl, 2),
                'percentual' => round($plPercent, 2),
            ];
        }

        $totalPL = $totalValorAtual - $totalCusto;
        $totalPLPercent = $totalCusto > 0 ? ($totalPL / $totalCusto) * 100 : 0.0;

        $analysis = new PortfolioAnalysis(
            composition: [],
            estimatedPL: $estimatedPL,
            totalCusto: round($totalCusto, 2),
            totalValorAtual: round($totalValorAtual, 2),
            totalPL: round($totalPL, 2),
            totalPLPercentual: round($totalPLPercent, 2),
        );

        $plSign = $totalPL >= 0 ? '+' : '';
        $plStr = number_format($totalPL, 2, ',', '.');

        return [
            'data' => [
                'positions' => $analysis->estimatedPL,
                'totalCusto' => $analysis->totalCusto,
                'totalValorAtual' => $analysis->totalValorAtual,
                'totalPL' => $analysis->totalPL,
                'totalPLPercentual' => $analysis->totalPLPercentual,
            ],
            'summary' => "Carteira com {$this->count($estimatedPL)} posições. P/L total: {$plSign}R\$ {$plStr} ({$plSign}".number_format($totalPLPercent, 2).'%).',
            'confidence' => 0.85,
        ];
    }

    /**
     * Delegate to RecommendationService.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function recommendBasket(int $limit): array
    {
        $cesta = $this->cestaRepo->findAtiva();

        if ($cesta === null) {
            return [
                'data' => [],
                'summary' => 'Não existe cesta ativa para gerar recomendações.',
                'confidence' => 0.0,
            ];
        }

        $result = $this->recommendationService->recommendForCesta($cesta->id(), $limit);

        if (empty($result->suggestedTickers)) {
            return [
                'data' => ['currentBasket' => $result->currentBasketSummary],
                'summary' => 'Não foi possível gerar recomendações. Verifique se existem embeddings de ativos disponíveis.',
                'confidence' => 0.0,
            ];
        }

        $tickerList = implode(', ', array_column($result->suggestedTickers, 'ticker'));

        return [
            'data' => [
                'suggestions' => $result->suggestedTickers,
                'currentBasket' => $result->currentBasketSummary,
                'confidence' => $result->confidence,
            ],
            'summary' => "Sugestão de cesta com {$this->count($result->suggestedTickers)} ativos: {$tickerList}. Confiança: ".round($result->confidence * 100).'%.',
            'confidence' => $result->confidence,
        ];
    }

    private function count(array $items): int
    {
        return count($items);
    }
}
