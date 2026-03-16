<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\TriggerType;
use App\Domain\Client\Events\ValorMensalAlterado;
use App\Infrastructure\AI\Agents\SimulatorAgent;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use Illuminate\Support\Facades\Log;

class TriggerSimulatorOnValorMensalAlterado
{
    public function __construct(
        private readonly SimulatorAgent $simulatorAgent,
        private readonly AgentNotificationDispatcher $dispatcher,
    ) {}

    public function handle(ValorMensalAlterado $event): void
    {
        try {
            $clienteId = $event->clienteId;

            $result = $this->simulatorAgent->execute(new AgentContext(
                clienteId: $clienteId,
                request: "Simulação de impacto da alteração de aporte para R\$ {$event->valorNovo}",
                triggerType: TriggerType::Event,
                additionalParams: [
                    'action' => 'simulate_aporte_change',
                    'cliente_id' => $clienteId,
                    'new_valor_mensal' => (float) $event->valorNovo,
                ],
            ));

            if (! empty($result->data) && ! isset($result->data['error'])) {
                $this->dispatcher->dispatch($result, $clienteId, 'valor_mensal_alterado');
            }
        } catch (\Throwable $e) {
            Log::error("TriggerSimulatorOnValorMensalAlterado: Failed for client {$event->clienteId}: {$e->getMessage()}");
        }
    }
}
