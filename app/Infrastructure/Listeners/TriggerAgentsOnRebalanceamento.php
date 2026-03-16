<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\TriggerType;
use App\Domain\Rebalancing\Events\RebalanceamentoTipoA;
use App\Domain\Rebalancing\Events\RebalanceamentoTipoB;
use App\Infrastructure\AI\Agents\PortfolioAnalystAgent;
use App\Infrastructure\AI\Agents\RiskAnalystAgent;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use Illuminate\Support\Facades\Log;

class TriggerAgentsOnRebalanceamento
{
    public function __construct(
        private readonly PortfolioAnalystAgent $portfolioAgent,
        private readonly RiskAnalystAgent $riskAgent,
        private readonly AgentNotificationDispatcher $dispatcher,
    ) {}

    public function handle(RebalanceamentoTipoA|RebalanceamentoTipoB $event): void
    {
        try {
            $clienteId = $event->clienteId;
            $tipoLabel = $event instanceof RebalanceamentoTipoA ? 'Tipo A' : 'Tipo B';

            // 1. Portfolio composition analysis
            $portfolioResult = $this->portfolioAgent->execute(new AgentContext(
                clienteId: $clienteId,
                request: "Análise pós-rebalanceamento {$tipoLabel}",
                triggerType: TriggerType::Event,
                additionalParams: [
                    'action' => 'analyze_composition',
                    'cliente_id' => $clienteId,
                ],
            ));

            if (! empty($portfolioResult->data) && ! isset($portfolioResult->data['error'])) {
                $this->dispatcher->dispatch($portfolioResult, $clienteId, 'rebalanceamento');
            }

            // 2. Risk calculation
            $riskResult = $this->riskAgent->execute(new AgentContext(
                clienteId: $clienteId,
                request: "Cálculo de risco pós-rebalanceamento {$tipoLabel}",
                triggerType: TriggerType::Event,
                additionalParams: [
                    'action' => 'calculate_risk',
                    'cliente_id' => $clienteId,
                ],
            ));

            if (! empty($riskResult->data) && ! isset($riskResult->data['error'])) {
                $this->dispatcher->dispatch($riskResult, $clienteId, 'rebalanceamento_risk');
            }
        } catch (\Throwable $e) {
            Log::error("TriggerAgentsOnRebalanceamento: Failed for client {$event->clienteId}: {$e->getMessage()}");
        }
    }
}
