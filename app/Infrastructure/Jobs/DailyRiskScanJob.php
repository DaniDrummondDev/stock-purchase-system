<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\TriggerType;
use App\Infrastructure\AI\Agents\RiskAnalystAgent;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DailyRiskScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct()
    {
        $this->queue = 'agents';
    }

    public function handle(RiskAnalystAgent $riskAgent, AgentNotificationDispatcher $dispatcher): void
    {
        Log::info('DailyRiskScanJob: Starting daily risk scan.');

        $clients = User::where('role', 'client')->get();

        foreach ($clients as $client) {
            try {
                $clienteId = $client->cliente_id;

                if ($clienteId === null) {
                    continue;
                }

                $context = new AgentContext(
                    clienteId: $clienteId,
                    request: 'Cálculo diário de risco da carteira',
                    triggerType: TriggerType::Scheduled,
                    additionalParams: [
                        'action' => 'calculate_risk',
                        'cliente_id' => $clienteId,
                    ],
                );

                $result = $riskAgent->execute($context);

                if (! empty($result->data) && ! isset($result->data['error'])) {
                    $dispatcher->dispatch($result, $clienteId, 'daily_risk_scan');
                }
            } catch (\Throwable $e) {
                Log::error("DailyRiskScanJob: Failed for client {$client->id}: {$e->getMessage()}");
            }
        }

        Log::info('DailyRiskScanJob: Completed.');
    }
}
