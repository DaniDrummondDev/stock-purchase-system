<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\TriggerType;
use App\Infrastructure\AI\Agents\MarketIntelligenceAgent;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use App\Infrastructure\Persistence\Models\AlertPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MarketBriefingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct()
    {
        $this->queue = 'agents';
    }

    public function handle(MarketIntelligenceAgent $marketAgent, AgentNotificationDispatcher $dispatcher): void
    {
        Log::info('MarketBriefingJob: Starting market briefing generation.');

        $context = new AgentContext(
            clienteId: 'system',
            request: 'Briefing diário de mercado',
            triggerType: TriggerType::Scheduled,
            additionalParams: [
                'action' => 'get_market_context',
            ],
        );

        try {
            $result = $marketAgent->execute($context);

            if (isset($result->data['error'])) {
                Log::warning('MarketBriefingJob: Agent returned error: '.$result->summary);

                return;
            }

            // Dispatch to all clients subscribed to market_briefing
            $subscribedClienteIds = AlertPreference::forTrigger('market_briefing')
                ->where('enabled', true)
                ->pluck('cliente_id');

            foreach ($subscribedClienteIds as $clienteId) {
                try {
                    $dispatcher->dispatch($result, $clienteId, 'market_briefing');
                } catch (\Throwable $e) {
                    Log::error("MarketBriefingJob: Failed to notify client {$clienteId}: {$e->getMessage()}");
                }
            }
        } catch (\Throwable $e) {
            Log::error('MarketBriefingJob: Failed to generate briefing: '.$e->getMessage());
        }

        Log::info('MarketBriefingJob: Completed.');
    }
}
