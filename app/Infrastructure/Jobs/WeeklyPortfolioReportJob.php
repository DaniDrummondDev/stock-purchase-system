<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\AgentResult;
use App\Domain\AI\Contracts\TriggerType;
use App\Infrastructure\AI\Agents\PortfolioAnalystAgent;
use App\Infrastructure\AI\Agents\RiskAnalystAgent;
use App\Infrastructure\AI\Agents\TaxAnalystAgent;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WeeklyPortfolioReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct()
    {
        $this->queue = 'agents';
    }

    public function handle(
        PortfolioAnalystAgent $portfolioAgent,
        RiskAnalystAgent $riskAgent,
        TaxAnalystAgent $taxAgent,
        AgentNotificationDispatcher $dispatcher,
    ): void {
        Log::info('WeeklyPortfolioReportJob: Starting weekly portfolio reports.');

        $clients = User::where('role', 'client')->get();

        foreach ($clients as $client) {
            try {
                $clienteId = $client->cliente_id;

                if ($clienteId === null) {
                    continue;
                }

                // 1. Portfolio composition
                $portfolioResult = $portfolioAgent->execute(new AgentContext(
                    clienteId: $clienteId,
                    request: 'Relatório semanal — composição da carteira',
                    triggerType: TriggerType::Scheduled,
                    additionalParams: [
                        'action' => 'analyze_composition',
                        'cliente_id' => $clienteId,
                    ],
                ));

                // 2. Cached risk
                $riskResult = $riskAgent->execute(new AgentContext(
                    clienteId: $clienteId,
                    request: 'Relatório semanal — risco da carteira',
                    triggerType: TriggerType::Scheduled,
                    additionalParams: [
                        'action' => 'get_cached_risk',
                        'cliente_id' => $clienteId,
                    ],
                ));

                // 3. Tax status
                $taxResult = $taxAgent->execute(new AgentContext(
                    clienteId: $clienteId,
                    request: 'Relatório semanal — situação fiscal',
                    triggerType: TriggerType::Scheduled,
                    additionalParams: [
                        'action' => 'analyze_tax_status',
                        'cliente_id' => $clienteId,
                    ],
                ));

                // Consolidate results
                $consolidatedResult = new AgentResult(
                    data: [
                        'portfolio' => $portfolioResult->data,
                        'risk' => $riskResult->data,
                        'tax' => $taxResult->data,
                    ],
                    summary: "Relatório semanal: {$portfolioResult->summary} | {$riskResult->summary} | {$taxResult->summary}",
                    confidence: min($portfolioResult->confidence, $riskResult->confidence, $taxResult->confidence),
                    metadata: [
                        'agent' => 'weekly_portfolio_report',
                        'agents_used' => ['portfolio_analyst', 'risk_analyst', 'tax_analyst'],
                    ],
                );

                $dispatcher->dispatch($consolidatedResult, $clienteId, 'weekly_portfolio_report');
            } catch (\Throwable $e) {
                Log::error("WeeklyPortfolioReportJob: Failed for client {$client->id}: {$e->getMessage()}");
            }
        }

        Log::info('WeeklyPortfolioReportJob: Completed.');
    }
}
