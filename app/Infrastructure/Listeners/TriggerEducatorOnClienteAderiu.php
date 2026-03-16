<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\TriggerType;
use App\Domain\Client\Events\ClienteAderiu;
use App\Infrastructure\AI\Agents\EducatorAgent;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use Illuminate\Support\Facades\Log;

class TriggerEducatorOnClienteAderiu
{
    public function __construct(
        private readonly EducatorAgent $educatorAgent,
        private readonly AgentNotificationDispatcher $dispatcher,
    ) {}

    public function handle(ClienteAderiu $event): void
    {
        try {
            $clienteId = $event->clienteId;

            $result = $this->educatorAgent->execute(new AgentContext(
                clienteId: $clienteId,
                request: 'Boas-vindas ao programa de compra programada',
                triggerType: TriggerType::Event,
                additionalParams: [
                    'action' => 'explain_concept',
                    'concept' => 'Boas-vindas ao programa de compra programada. Explique como funciona.',
                    'cliente_id' => $clienteId,
                ],
            ));

            if (! empty($result->data) && ! isset($result->data['error'])) {
                $this->dispatcher->dispatch($result, $clienteId, 'cliente_aderiu');
            }
        } catch (\Throwable $e) {
            Log::error("TriggerEducatorOnClienteAderiu: Failed for client {$event->clienteId}: {$e->getMessage()}");
        }
    }
}
