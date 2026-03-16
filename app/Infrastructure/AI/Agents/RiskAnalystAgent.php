<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Agents;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\AgentResult;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\AI\RiskAnalysis\Services\RiskAnalysisService;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\Kafka\KafkaProducer;
use App\Infrastructure\Kafka\Messages\RiscoAlertaMessage;
use App\Infrastructure\Persistence\Models\AnaliseRiscoCache;
use App\Infrastructure\Persistence\Models\Cotacao as CotacaoModel;

final class RiskAnalystAgent implements FinanceAgentInterface
{
    public function __construct(
        private readonly CustodiaRepositoryInterface $custodiaRepo,
        private readonly CotacaoRepositoryInterface $cotacaoRepo,
        private readonly RiskAnalysisService $riskService,
        private readonly KafkaProducer $kafkaProducer,
    ) {}

    public function getName(): string
    {
        return 'risk_analyst';
    }

    public function getDescription(): string
    {
        return 'Analisa o risco da carteira do cliente calculando concentração (HHI), volatilidade histórica e diversificação. Gera score de risco, alertas e publica notificações via Kafka quando o risco é elevado.';
    }

    public function getParameterSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['calculate_risk', 'get_cached_risk'],
                    'description' => 'A ação a executar: calculate_risk (calcula risco completo), get_cached_risk (retorna último cálculo em cache)',
                ],
                'cliente_id' => [
                    'type' => 'string',
                    'description' => 'UUID do cliente (obrigatório)',
                ],
            ],
            'required' => ['action', 'cliente_id'],
        ];
    }

    public function execute(AgentContext $context): AgentResult
    {
        $params = $context->additionalParams;
        $action = $params['action'] ?? 'calculate_risk';
        $clienteId = $params['cliente_id'] ?? $context->clienteId;

        $startTime = hrtime(true);

        try {
            $result = match ($action) {
                'calculate_risk' => $this->calculateRisk($clienteId),
                'get_cached_risk' => $this->getCachedRisk($clienteId),
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
     * Calculate full risk analysis for a client's portfolio.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function calculateRisk(string $clienteId): array
    {
        $custodias = $this->custodiaRepo->findByClienteId($clienteId);

        if (empty($custodias)) {
            return [
                'data' => [],
                'summary' => 'Cliente não possui custódia para análise de risco.',
                'confidence' => 1.0,
            ];
        }

        // Load historical prices (last 20 trading days) for each ticker
        $tickers = array_map(fn ($c) => $c->ticker(), $custodias);
        $historicalPrices = [];

        foreach ($tickers as $ticker) {
            $prices = CotacaoModel::where('ticker', strtoupper($ticker))
                ->where('tipo_mercado', 'padrao')
                ->orderBy('data_pregao', 'desc')
                ->limit(20)
                ->pluck('preco_fechamento')
                ->map(fn ($p) => (float) $p)
                ->all();

            $historicalPrices[$ticker] = $prices;
        }

        // Calculate risk via domain service
        $riskScore = $this->riskService->analyze($custodias, $historicalPrices);
        $metrics = $this->riskService->buildMetrics($custodias, $historicalPrices);

        // Cache the result with 24h TTL
        $cache = AnaliseRiscoCache::create([
            'cliente_id' => $clienteId,
            'score_risco' => $riskScore->score(),
            'alertas' => $metrics->alerts,
            'recomendacoes' => $this->buildRecomendacoes($riskScore->band()->value, $metrics->alerts),
            'valid_until' => now()->addHours(24),
        ]);

        // Publish Kafka alert if score >= 0.7
        if ($riskScore->requiresAlert()) {
            $message = new RiscoAlertaMessage(
                clienteId: $clienteId,
                score: $riskScore->score(),
                band: $riskScore->band()->value,
                alertas: $metrics->alerts,
            );

            $this->kafkaProducer->produce(
                RiscoAlertaMessage::topic(),
                $message->toArray(),
            );
        }

        $bandLabel = $riskScore->band()->label();
        $scoreFormatted = number_format($riskScore->score() * 100, 1, ',', '.');

        return [
            'data' => [
                'score' => $riskScore->score(),
                'band' => $riskScore->band()->value,
                'bandLabel' => $bandLabel,
                'herfindahlIndex' => $metrics->herfindahlIndex,
                'volatility' => $metrics->volatility,
                'maxConcentration' => $metrics->maxConcentration,
                'tickerCount' => $metrics->tickerCount,
                'tickerWeights' => $metrics->tickerWeights,
                'alerts' => $metrics->alerts,
                'cachedUntil' => $cache->valid_until->toIso8601String(),
            ],
            'summary' => "Risco da carteira: {$scoreFormatted}% ({$bandLabel}). "
                .count($metrics->alerts).' alerta(s) identificado(s).',
            'confidence' => 0.9,
        ];
    }

    /**
     * Get cached risk analysis for a client.
     *
     * @return array{data: array, summary: string, confidence: float}
     */
    private function getCachedRisk(string $clienteId): array
    {
        $cached = AnaliseRiscoCache::forCliente($clienteId)
            ->active()
            ->latest()
            ->first();

        if ($cached === null) {
            return [
                'data' => [],
                'summary' => 'Nenhuma análise de risco em cache para este cliente. Execute calculate_risk primeiro.',
                'confidence' => 0.0,
            ];
        }

        return [
            'data' => [
                'score' => (float) $cached->score_risco,
                'alertas' => $cached->alertas,
                'recomendacoes' => $cached->recomendacoes,
                'cachedUntil' => $cached->valid_until->toIso8601String(),
                'calculatedAt' => $cached->created_at->toIso8601String(),
            ],
            'summary' => 'Análise de risco recuperada do cache. Score: '
                .number_format((float) $cached->score_risco * 100, 1, ',', '.').'%.',
            'confidence' => 0.85,
        ];
    }

    /**
     * Build recommendations text based on risk band and alerts.
     */
    private function buildRecomendacoes(string $band, array $alerts): string
    {
        $recomendacoes = match ($band) {
            'conservative' => 'Carteira com perfil conservador. Manter estratégia atual.',
            'moderate' => 'Risco moderado. Monitorar concentração e volatilidade.',
            'aggressive' => 'Risco elevado. Considerar diversificação e redução de posições concentradas.',
            'critical' => 'Risco crítico! Rebalanceamento urgente recomendado. Diversificar posições imediatamente.',
            default => 'Avaliar composição da carteira.',
        };

        if (! empty($alerts)) {
            $recomendacoes .= ' Alertas: '.implode('; ', $alerts).'.';
        }

        return $recomendacoes;
    }
}
