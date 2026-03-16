<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\TriggerType;
use App\Infrastructure\AI\Agents\TaxAnalystAgent;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TaxAlertMonthlyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct()
    {
        $this->queue = 'agents';
    }

    public function handle(TaxAnalystAgent $taxAgent, AgentNotificationDispatcher $dispatcher): void
    {
        Log::info('TaxAlertMonthlyJob: Starting monthly tax alert scan.');

        $clients = User::where('role', 'client')->get();

        foreach ($clients as $client) {
            try {
                $clienteId = $client->cliente_id;

                if ($clienteId === null) {
                    continue;
                }

                $context = new AgentContext(
                    clienteId: $clienteId,
                    request: 'Alerta fiscal mensal',
                    triggerType: TriggerType::Scheduled,
                    additionalParams: [
                        'action' => 'analyze_tax_status',
                        'cliente_id' => $clienteId,
                    ],
                );

                $result = $taxAgent->execute($context);

                if (! empty($result->data) && ! isset($result->data['error'])) {
                    $dispatcher->dispatch($result, $clienteId, 'tax_alert_monthly');
                }
            } catch (\Throwable $e) {
                Log::error("TaxAlertMonthlyJob: Failed for client {$client->id}: {$e->getMessage()}");
            }
        }

        Log::info('TaxAlertMonthlyJob: Completed.');
    }
}
