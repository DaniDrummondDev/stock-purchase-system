<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\TriggerType;
use App\Domain\PurchaseEngine\Events\CompraDistribuida;
use App\Infrastructure\AI\Agents\PortfolioAnalystAgent;
use App\Infrastructure\AI\Agents\TaxAnalystAgent;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use Illuminate\Support\Facades\Log;

class TriggerAgentsOnCompraDistribuida
{
    public function __construct(
        private readonly PortfolioAnalystAgent $portfolioAgent,
        private readonly TaxAnalystAgent $taxAgent,
        private readonly AgentNotificationDispatcher $dispatcher,
    ) {}

    public function handle(CompraDistribuida $event): void
    {
        try {
            $clienteId = $event->clienteId;

            // 1. Portfolio composition analysis
            $portfolioResult = $this->portfolioAgent->execute(new AgentContext(
                clienteId: $clienteId,
                request: "Análise pós-compra distribuída do ticker {$event->ticker}",
                triggerType: TriggerType::Event,
                additionalParams: [
                    'action' => 'analyze_composition',
                    'cliente_id' => $clienteId,
                ],
            ));

            if (! empty($portfolioResult->data) && ! isset($portfolioResult->data['error'])) {
                $this->dispatcher->dispatch($portfolioResult, $clienteId, 'compra_distribuida');
            }

            // 2. Tax status analysis
            $taxResult = $this->taxAgent->execute(new AgentContext(
                clienteId: $clienteId,
                request: "Situação fiscal pós-compra distribuída do ticker {$event->ticker}",
                triggerType: TriggerType::Event,
                additionalParams: [
                    'action' => 'analyze_tax_status',
                    'cliente_id' => $clienteId,
                ],
            ));

            if (! empty($taxResult->data) && ! isset($taxResult->data['error'])) {
                $this->dispatcher->dispatch($taxResult, $clienteId, 'compra_distribuida_tax');
            }
        } catch (\Throwable $e) {
            Log::error("TriggerAgentsOnCompraDistribuida: Failed for compra {$event->compraId}: {$e->getMessage()}");
        }
    }
}
