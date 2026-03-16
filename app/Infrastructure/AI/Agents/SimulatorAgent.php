<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\AgentResult;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;

final class SimulatorAgent implements FinanceAgentInterface
{
    private const LIMITE_ISENCAO = 20000.00;

    private const ALIQUOTA_IR = 0.20;

    public function __construct(
        private readonly CestaRepositoryInterface $cestaRepo,
        private readonly CustodiaRepositoryInterface $custodiaRepo,
        private readonly CotacaoRepositoryInterface $cotacaoRepo,
    ) {}

    public function getName(): string
    {
        return 'simulator';
    }

    public function getDescription(): string
    {
        return 'Realiza projeções e simulações what-if da carteira: simula alteração de aporte mensal, troca de ativos na cesta e projeção de evolução patrimonial com base em dados históricos.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['simulate_aporte_change', 'simulate_ticker_swap', 'project_portfolio'],
                    'description' => 'A ação a executar: simulate_aporte_change (simula novo valor de aporte), simulate_ticker_swap (troca de ativo), project_portfolio (projeção de carteira)',
                ],
                'cliente_id' => [
                    'type' => 'string',
                    'description' => 'UUID do cliente (obrigatório)',
                ],
                'new_valor_mensal' => [
                    'type' => 'number',
                    'description' => 'Novo valor de aporte mensal em R$ (para simulate_aporte_change)',
                ],
                'old_ticker' => [
                    'type' => 'string',
                    'description' => 'Ticker do ativo a ser substituído (para simulate_ticker_swap)',
                ],
                'new_ticker' => [
                    'type' => 'string',
                    'description' => 'Ticker do novo ativo (para simulate_ticker_swap)',
                ],
                'months' => [
                    'type' => 'integer',
                    'description' => 'Horizonte da projeção em meses (default: 12)',
                ],
            ],
            'required' => ['action', 'cliente_id'],
        ];
    }

    public function execute(AgentContext $context): AgentResult
    {
        $params = $context->additionalParams;
        $action = $params['action'] ?? 'project_portfolio';
        $clienteId = $params['cliente_id'] ?? $context->clienteId;

        $startTime = hrtime(true);

        try {
            $result = match ($action) {
                'simulate_aporte_change' => $this->simulateAporteChange(
                    $clienteId,
                    (float) ($params['new_valor_mensal'] ?? throw new \InvalidArgumentException('new_valor_mensal é obrigatório para simulate_aporte_change')),
                    (int) ($params['months'] ?? 12),
                ),
                'simulate_ticker_swap' => $this->simulateTickerSwap(
                    $clienteId,
                    $params['old_ticker'] ?? throw new \InvalidArgumentException('old_ticker é obrigatório para simulate_ticker_swap'),
                    $params['new_ticker'] ?? throw new \InvalidArgumentException('new_ticker é obrigatório para simulate_ticker_swap'),
                ),
                'project_portfolio' => $this->projectPortfolio(
                    $clienteId,
                    (int) ($params['months'] ?? 12),
                ),
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
     * Simulate the impact of changing the monthly aporte value.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function simulateAporteChange(string $clienteId, float $newValorMensal, int $months): array
    {
        $custodias = $this->custodiaRepo->findByClienteId($clienteId);
        $cesta = $this->cestaRepo->findAtiva();

        if ($cesta === null) {
            return [
                'data' => [],
                'summary' => 'Não existe cesta ativa para simulação.',
                'confidence' => 0.0,
            ];
        }

        $percentuais = $cesta->percentualPorTicker();
        $tickers = array_keys($percentuais);
        $cotacoes = $this->cotacaoRepo->findLatestByTickers($tickers);
        $cotacaoMap = [];
        foreach ($cotacoes as $cotacao) {
            $cotacaoMap[$cotacao->ticker()] = $cotacao->precoFechamento();
        }

        // Build current position map
        $currentPositions = [];
        foreach ($custodias as $custodia) {
            $currentPositions[$custodia->ticker()] = [
                'quantidade' => $custodia->quantidade(),
                'precoMedio' => $custodia->precoMedio(),
            ];
        }

        // Project: 3 purchase dates per month (5, 15, 25), aporte divided by 3
        $aportePorCompra = $newValorMensal / 3;
        $projections = [];

        foreach ($percentuais as $ticker => $percentual) {
            $price = $cotacaoMap[$ticker] ?? 0;
            if ($price <= 0) {
                continue;
            }

            $currentQty = $currentPositions[$ticker]['quantidade'] ?? 0;
            $valorPorCompra = $aportePorCompra * ($percentual / 100);
            $qtyPerPurchase = floor($valorPorCompra / $price);
            $totalNewQty = $qtyPerPurchase * 3 * $months; // 3 purchases/month

            $projections[] = [
                'ticker' => $ticker,
                'percentualCesta' => $percentual,
                'precoAtual' => round($price, 2),
                'quantidadeAtual' => $currentQty,
                'quantidadeProjetada' => $currentQty + $totalNewQty,
                'crescimentoQuantidade' => $totalNewQty,
                'valorProjetado' => round(($currentQty + $totalNewQty) * $price, 2),
            ];
        }

        $totalProjectedValue = array_sum(array_column($projections, 'valorProjetado'));

        return [
            'data' => [
                'newValorMensal' => $newValorMensal,
                'months' => $months,
                'purchasesPerMonth' => 3,
                'projections' => $projections,
                'totalValorProjetado' => round($totalProjectedValue, 2),
            ],
            'summary' => 'Simulação de aporte de R$ '.number_format($newValorMensal, 2, ',', '.')
                ."/mês por {$months} meses. Valor total projetado da carteira: R$ "
                .number_format($totalProjectedValue, 2, ',', '.')
                .'. Compras realizadas nos dias 5, 15 e 25 de cada mês.',
            'confidence' => 0.7,
        ];
    }

    /**
     * Simulate swapping one ticker for another in the portfolio.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function simulateTickerSwap(string $clienteId, string $oldTicker, string $newTicker): array
    {
        $custodia = $this->custodiaRepo->findByClienteIdAndTicker($clienteId, $oldTicker);

        if ($custodia === null) {
            return [
                'data' => [],
                'summary' => "Cliente não possui custódia do ativo {$oldTicker}.",
                'confidence' => 1.0,
            ];
        }

        // Get current prices
        $cotacoes = $this->cotacaoRepo->findLatestByTickers([$oldTicker, $newTicker]);
        $cotacaoMap = [];
        foreach ($cotacoes as $cotacao) {
            $cotacaoMap[$cotacao->ticker()] = $cotacao->precoFechamento();
        }

        $precoOld = $cotacaoMap[$oldTicker] ?? $custodia->precoMedio();
        $precoNew = $cotacaoMap[$newTicker] ?? null;

        if ($precoNew === null) {
            return [
                'data' => [],
                'summary' => "Não foi possível obter cotação do ativo {$newTicker}.",
                'confidence' => 0.0,
            ];
        }

        $valorVenda = $custodia->quantidade() * $precoOld;
        $custoTotal = $custodia->quantidade() * $custodia->precoMedio();
        $lucro = $valorVenda - $custoTotal;

        // Estimate IR: 20% on profit if monthly sales > R$ 20k
        $estimatedIR = 0.0;
        if ($valorVenda > self::LIMITE_ISENCAO && $lucro > 0) {
            $estimatedIR = round($lucro * self::ALIQUOTA_IR, 2);
        }

        $valorLiquido = $valorVenda - $estimatedIR;
        $newQuantidade = (int) floor($valorLiquido / $precoNew);
        $valorNewPosition = $newQuantidade * $precoNew;
        $sobra = round($valorLiquido - $valorNewPosition, 2);

        return [
            'data' => [
                'oldTicker' => $oldTicker,
                'newTicker' => $newTicker,
                'quantidadeVendida' => $custodia->quantidade(),
                'precoVenda' => round($precoOld, 2),
                'valorVenda' => round($valorVenda, 2),
                'custoTotal' => round($custoTotal, 2),
                'lucro' => round($lucro, 2),
                'estimatedIR' => $estimatedIR,
                'valorLiquido' => round($valorLiquido, 2),
                'precoCompraNew' => round($precoNew, 2),
                'quantidadeCompra' => $newQuantidade,
                'valorNewPosition' => round($valorNewPosition, 2),
                'sobra' => $sobra,
            ],
            'summary' => "Simulação de troca de {$custodia->quantidade()}x {$oldTicker} por {$newTicker}: "
                .'venda de R$ '.number_format($valorVenda, 2, ',', '.')
                .($lucro >= 0 ? ' (lucro R$ ' : ' (prejuízo R$ ').number_format(abs($lucro), 2, ',', '.').').'
                .($estimatedIR > 0 ? ' IR estimado: R$ '.number_format($estimatedIR, 2, ',', '.').'.' : ' Isento de IR.')
                ." Compra de {$newQuantidade}x {$newTicker} a R$ ".number_format($precoNew, 2, ',', '.')
                .'. Sobra: R$ '.number_format($sobra, 2, ',', '.').'.',
            'confidence' => 0.75,
        ];
    }

    /**
     * Project portfolio value based on historical average returns.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function projectPortfolio(string $clienteId, int $months): array
    {
        $custodias = $this->custodiaRepo->findByClienteId($clienteId);

        if (empty($custodias)) {
            return [
                'data' => [],
                'summary' => 'Cliente não possui custódia para projeção.',
                'confidence' => 0.0,
            ];
        }

        $tickers = array_map(fn ($c) => $c->ticker(), $custodias);
        $cotacoes = $this->cotacaoRepo->findLatestByTickers($tickers);
        $cotacaoMap = [];
        foreach ($cotacoes as $cotacao) {
            $cotacaoMap[$cotacao->ticker()] = $cotacao->precoFechamento();
        }

        // Get last 20 days of cotacoes for average return calculation
        $historicalCotacoes = $this->cotacaoRepo->findByTickersLastDays($tickers, 20);

        $projections = [];
        $totalAtual = 0.0;
        $totals3m = 0.0;
        $totals6m = 0.0;
        $totals12m = 0.0;

        foreach ($custodias as $custodia) {
            $ticker = $custodia->ticker();
            $precoAtual = $cotacaoMap[$ticker] ?? $custodia->precoMedio();
            $valorAtual = $custodia->quantidade() * $precoAtual;
            $totalAtual += $valorAtual;

            // Calculate average daily return from historical data
            $dailyReturn = $this->calculateAverageDailyReturn($historicalCotacoes[$ticker] ?? [], $precoAtual);

            // Project at 3, 6, 12 months (approx 21 trading days per month)
            $valor3m = $valorAtual * pow(1 + $dailyReturn, 21 * 3);
            $valor6m = $valorAtual * pow(1 + $dailyReturn, 21 * 6);
            $valor12m = $valorAtual * pow(1 + $dailyReturn, 21 * 12);

            $totals3m += $valor3m;
            $totals6m += $valor6m;
            $totals12m += $valor12m;

            $projections[] = [
                'ticker' => $ticker,
                'quantidade' => $custodia->quantidade(),
                'precoAtual' => round($precoAtual, 2),
                'valorAtual' => round($valorAtual, 2),
                'avgDailyReturn' => round($dailyReturn * 100, 4),
                'projecao3m' => round($valor3m, 2),
                'projecao6m' => round($valor6m, 2),
                'projecao12m' => round($valor12m, 2),
            ];
        }

        return [
            'data' => [
                'projections' => $projections,
                'totalAtual' => round($totalAtual, 2),
                'totalProjecao3m' => round($totals3m, 2),
                'totalProjecao6m' => round($totals6m, 2),
                'totalProjecao12m' => round($totals12m, 2),
                'disclaimer' => 'Projeção baseada em retornos históricos dos últimos 20 pregões. Resultados reais podem variar significativamente.',
            ],
            'summary' => 'Projeção da carteira com '.count($projections).' ativos. '
                .'Valor atual: R$ '.number_format($totalAtual, 2, ',', '.').'. '
                .'Projeção 3m: R$ '.number_format($totals3m, 2, ',', '.').', '
                .'6m: R$ '.number_format($totals6m, 2, ',', '.').', '
                .'12m: R$ '.number_format($totals12m, 2, ',', '.').'. '
                .'⚠ Projeção baseada em retornos históricos, resultados reais podem variar.',
            'confidence' => 0.5,
        ];
    }

    /**
     * Calculate average daily return from historical cotacoes.
     */
    private function calculateAverageDailyReturn(array $cotacoes, float $currentPrice): float
    {
        if (empty($cotacoes) || count($cotacoes) < 2) {
            return 0.0;
        }

        $returns = [];
        for ($i = 1; $i < count($cotacoes); $i++) {
            $prevPrice = $cotacoes[$i - 1];
            $currPrice = $cotacoes[$i];

            if ($prevPrice > 0) {
                $returns[] = ($currPrice - $prevPrice) / $prevPrice;
            }
        }

        if (empty($returns)) {
            return 0.0;
        }

        return array_sum($returns) / count($returns);
    }
}
