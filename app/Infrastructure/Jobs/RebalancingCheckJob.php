<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\TriggerType;
use App\Infrastructure\AI\Agents\PortfolioAnalystAgent;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RebalancingCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct()
    {
        $this->queue = 'agents';
    }

    public function handle(PortfolioAnalystAgent $portfolioAgent, AgentNotificationDispatcher $dispatcher): void
    {
        Log::info('RebalancingCheckJob: Starting rebalancing check.');

        $clients = User::where('role', 'client')->get();

        foreach ($clients as $client) {
            try {
                $clienteId = $client->cliente_id;

                if ($clienteId === null) {
                    continue;
                }

                $context = new AgentContext(
                    clienteId: $clienteId,
                    request: 'Verificação de rebalanceamento da carteira',
                    triggerType: TriggerType::Scheduled,
                    additionalParams: [
                        'action' => 'analyze_composition',
                        'cliente_id' => $clienteId,
                    ],
                );

                $result = $portfolioAgent->execute($context);

                if (isset($result->data['error']) || empty($result->data['composition'])) {
                    continue;
                }

                // Check if any ticker has deviation > 5.0 pp
                $needsRebalancing = false;
                foreach ($result->data['composition'] as $position) {
                    if (abs($position['deviationPp'] ?? 0) > 5.0) {
                        $needsRebalancing = true;

                        break;
                    }
                }

                if ($needsRebalancing) {
                    $dispatcher->dispatch($result, $clienteId, 'rebalancing_check');
                }
            } catch (\Throwable $e) {
                Log::error("RebalancingCheckJob: Failed for client {$client->id}: {$e->getMessage()}");
            }
        }

        Log::info('RebalancingCheckJob: Completed.');
    }
}
