<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\AgentResult;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class TaxAnalystAgent implements FinanceAgentInterface
{
    private const LIMITE_ISENCAO = 20000.00; // R$ 20.000,00

    private const ALERTA_PROXIMIDADE = 15000.00; // R$ 15.000,00

    private const ALIQUOTA_IR = 0.20; // 20%

    public function __construct(
        private readonly CustodiaRepositoryInterface $custodiaRepo,
        private readonly CotacaoRepositoryInterface $cotacaoRepo,
    ) {}

    public function getName(): string
    {
        return 'tax_analyst';
    }

    public function getDescription(): string
    {
        return 'Analisa situação fiscal do cliente: IR dedo-duro acumulado, vendas mensais, proximidade do limite de isenção (R$ 20k) e simulação de imposto sobre venda de ativos.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['analyze_tax_status', 'simulate_sale_tax'],
                    'description' => 'A ação a executar: analyze_tax_status (situação fiscal atual), simulate_sale_tax (simula IR de uma venda)',
                ],
                'cliente_id' => [
                    'type' => 'string',
                    'description' => 'UUID do cliente (obrigatório)',
                ],
                'ticker' => [
                    'type' => 'string',
                    'description' => 'Ticker do ativo para simulação de venda (obrigatório para simulate_sale_tax)',
                ],
                'quantidade' => [
                    'type' => 'integer',
                    'description' => 'Quantidade de ações para simulação de venda (obrigatório para simulate_sale_tax)',
                ],
            ],
            'required' => ['action', 'cliente_id'],
        ];
    }

    public function execute(AgentContext $context): AgentResult
    {
        $params = $context->additionalParams;
        $action = $params['action'] ?? 'analyze_tax_status';
        $clienteId = $params['cliente_id'] ?? $context->clienteId;

        $startTime = hrtime(true);

        try {
            $result = match ($action) {
                'analyze_tax_status' => $this->analyzeTaxStatus($clienteId),
                'simulate_sale_tax' => $this->simulateSaleTax(
                    $clienteId,
                    $params['ticker'] ?? throw new \InvalidArgumentException('Ticker é obrigatório para simulate_sale_tax'),
                    $params['quantidade'] ?? throw new \InvalidArgumentException('Quantidade é obrigatória para simulate_sale_tax'),
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
     * Analyze current tax status for the client.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function analyzeTaxStatus(string $clienteId): array
    {
        $mesAtual = now()->format('Y-m');

        // Query operacoes_ir for dedo-duro totals
        $dedoDuroTotal = DB::table('operacoes_ir')
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'dedo_duro')
            ->sum('imposto');

        $dedoDuroMes = DB::table('operacoes_ir')
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'dedo_duro')
            ->where('mes_referencia', $mesAtual)
            ->sum('imposto');

        // Monthly sales total
        $vendasMes = DB::table('operacoes_ir')
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'venda')
            ->where('mes_referencia', $mesAtual)
            ->sum('valor_operacao');

        $impostoVendasMes = DB::table('operacoes_ir')
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'venda')
            ->where('mes_referencia', $mesAtual)
            ->sum('imposto');

        // Generate alerts
        $alertas = [];
        $vendasMes = (float) $vendasMes;

        if ($vendasMes >= self::ALERTA_PROXIMIDADE) {
            $remaining = self::LIMITE_ISENCAO - $vendasMes;
            $remainingFormatted = number_format(max(0, $remaining), 2, ',', '.');
            $alertas[] = 'Vendas mensais de R$ '.number_format($vendasMes, 2, ',', '.')
                ." se aproximam do limite de isenção (R$ 20.000). Margem restante: R$ {$remainingFormatted}.";
        }

        if ($vendasMes > self::LIMITE_ISENCAO) {
            $alertas[] = 'Limite de isenção de R$ 20.000 já ultrapassado neste mês. Vendas adicionais com lucro serão tributadas em 20%.';
        }

        $isento = $vendasMes <= self::LIMITE_ISENCAO;

        return [
            'data' => [
                'mesReferencia' => $mesAtual,
                'dedoDuroTotal' => round((float) $dedoDuroTotal, 2),
                'dedoDuroMes' => round((float) $dedoDuroMes, 2),
                'vendasMes' => round($vendasMes, 2),
                'impostoVendasMes' => round((float) $impostoVendasMes, 2),
                'limiteIsencao' => self::LIMITE_ISENCAO,
                'margemRestante' => round(max(0, self::LIMITE_ISENCAO - $vendasMes), 2),
                'isento' => $isento,
                'alertas' => $alertas,
            ],
            'summary' => 'Situação fiscal do mês '.$mesAtual.': vendas de R$ '
                .number_format($vendasMes, 2, ',', '.')
                .'. Dedo-duro acumulado: R$ '.number_format((float) $dedoDuroTotal, 2, ',', '.')
                .'. '.($isento ? 'Isento de IR sobre vendas.' : 'Acima do limite de isenção.'),
            'confidence' => 0.95,
        ];
    }

    /**
     * Simulate tax impact of selling a position.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function simulateSaleTax(string $clienteId, string $ticker, int $quantidade): array
    {
        $custodia = $this->custodiaRepo->findByClienteIdAndTicker($clienteId, $ticker);

        if ($custodia === null) {
            return [
                'data' => [],
                'summary' => "Cliente não possui custódia do ativo {$ticker}.",
                'confidence' => 1.0,
            ];
        }

        if ($quantidade > $custodia->quantidade()) {
            return [
                'data' => [],
                'summary' => "Quantidade solicitada ({$quantidade}) excede a custódia disponível ({$custodia->quantidade()}) de {$ticker}.",
                'confidence' => 1.0,
            ];
        }

        // Get current price
        $cotacao = $this->cotacaoRepo->findLatestByTicker($ticker);
        $precoAtual = $cotacao ? $cotacao->precoFechamento() : $custodia->precoMedio();

        $valorVenda = $quantidade * $precoAtual;
        $custoTotal = $quantidade * $custodia->precoMedio();
        $lucro = $valorVenda - $custoTotal;

        // Get current month sales total
        $mesAtual = now()->format('Y-m');
        $vendasMesAtual = (float) DB::table('operacoes_ir')
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'venda')
            ->where('mes_referencia', $mesAtual)
            ->sum('valor_operacao');

        $vendasAposSimulacao = $vendasMesAtual + $valorVenda;
        $ultrapassaLimite = $vendasAposSimulacao > self::LIMITE_ISENCAO;

        // Calculate IR: only if above limit and there's profit
        $valorIR = 0.0;
        $isento = true;

        if ($ultrapassaLimite && $lucro > 0) {
            $valorIR = round($lucro * self::ALIQUOTA_IR, 2);
            $isento = false;
        }

        $lucroFormatted = number_format($lucro, 2, ',', '.');
        $irFormatted = number_format($valorIR, 2, ',', '.');

        return [
            'data' => [
                'ticker' => $ticker,
                'quantidade' => $quantidade,
                'precoMedio' => round($custodia->precoMedio(), 2),
                'precoAtual' => round($precoAtual, 2),
                'valorVenda' => round($valorVenda, 2),
                'custoTotal' => round($custoTotal, 2),
                'lucro' => round($lucro, 2),
                'vendasMesAntes' => round($vendasMesAtual, 2),
                'vendasMesApos' => round($vendasAposSimulacao, 2),
                'limiteIsencao' => self::LIMITE_ISENCAO,
                'ultrapassaLimite' => $ultrapassaLimite,
                'isento' => $isento,
                'aliquota' => $isento ? 0.0 : self::ALIQUOTA_IR,
                'valorIR' => $valorIR,
            ],
            'summary' => "Simulação de venda de {$quantidade}x {$ticker}: "
                .($lucro >= 0 ? 'lucro' : 'prejuízo')." de R$ {$lucroFormatted}. "
                .($isento
                    ? 'Isento de IR (vendas mensais dentro do limite de R$ 20k).'
                    : "IR estimado: R$ {$irFormatted} (20% sobre lucro)."),
            'confidence' => 0.9,
        ];
    }
}
