<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\AI\Contracts\EmbeddingServiceInterface;
use App\Domain\AI\Contracts\RecommendationServiceInterface;
use App\Domain\AI\DataProviders\DataProviderRegistry;
use App\Domain\AI\Orchestrator\OrchestratorPromptBuilder;
use App\Domain\AI\RiskAnalysis\Services\RiskAnalysisService;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Events\ClienteAderiu;
use App\Domain\Client\Events\ValorMensalAlterado;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Events\CotacoesImportadas;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Domain\PurchaseEngine\Events\CompraDistribuida;
use App\Domain\Rebalancing\Events\RebalanceamentoTipoA;
use App\Domain\Rebalancing\Events\RebalanceamentoTipoB;
use App\Infrastructure\AI\Agents\EducatorAgent;
use App\Infrastructure\AI\Agents\MarketIntelligenceAgent;
use App\Infrastructure\AI\Agents\PortfolioAnalystAgent;
use App\Infrastructure\AI\Agents\RiskAnalystAgent;
use App\Infrastructure\AI\Agents\SimulatorAgent;
use App\Infrastructure\AI\Agents\TaxAnalystAgent;
use App\Infrastructure\AI\AiConfigResolver;
use App\Infrastructure\AI\DataProviders\BcbProvider;
use App\Infrastructure\AI\DataProviders\CotahistProvider;
use App\Infrastructure\AI\DataProviders\DataProviderManager;
use App\Infrastructure\AI\Notifications\AgentNotificationDispatcher;
use App\Infrastructure\AI\Notifications\NotificationPrioritizer;
use App\Infrastructure\AI\Notifications\ProactiveChatInjector;
use App\Infrastructure\AI\Orchestrator\AgentOrchestrator;
use App\Infrastructure\AI\Safety\AgentCircuitBreaker;
use App\Infrastructure\AI\Safety\AgentTimeoutConfig;
use App\Infrastructure\AI\Safety\SafeAgentExecutor;
use App\Infrastructure\AI\Safety\ScopeGuardrail;
use App\Infrastructure\AI\Services\EmbeddingService;
use App\Infrastructure\AI\Services\RecommendationService;
use App\Infrastructure\Kafka\KafkaProducer;
use App\Infrastructure\Listeners\TriggerAgentsOnCompraDistribuida;
use App\Infrastructure\Listeners\TriggerAgentsOnRebalanceamento;
use App\Infrastructure\Listeners\TriggerEducatorOnClienteAderiu;
use App\Infrastructure\Listeners\TriggerSimulatorOnValorMensalAlterado;
use App\Infrastructure\Listeners\UpdateEmbeddingsOnCotacoesImported;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AiAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataProviderRegistry::class, function () {
            $registry = new DataProviderRegistry;

            $registry->register($this->app->make(CotahistProvider::class));
            $registry->register($this->app->make(BcbProvider::class));

            return $registry;
        });

        $this->app->singleton(DataProviderManager::class, function ($app) {
            return new DataProviderManager(
                registry: $app->make(DataProviderRegistry::class),
                cache: $app->make(Cache::class),
            );
        });

        $this->app->singleton(OrchestratorPromptBuilder::class);

        $this->app->singleton(AgentCircuitBreaker::class, function ($app) {
            return new AgentCircuitBreaker(
                cache: $app->make(Cache::class),
                failureThreshold: (int) config('ai.agents.circuit_breaker.failure_threshold', 3),
                cooldownSeconds: (int) config('ai.agents.circuit_breaker.cooldown_seconds', 600),
            );
        });

        $this->app->singleton(AgentTimeoutConfig::class, function () {
            return AgentTimeoutConfig::fromConfig();
        });

        $this->app->singleton(SafeAgentExecutor::class, function ($app) {
            return new SafeAgentExecutor(
                circuitBreaker: $app->make(AgentCircuitBreaker::class),
                timeoutConfig: $app->make(AgentTimeoutConfig::class),
            );
        });

        $this->app->singleton(AgentOrchestrator::class, function ($app) {
            return new AgentOrchestrator(
                promptBuilder: $app->make(OrchestratorPromptBuilder::class),
                configResolver: $app->make(AiConfigResolver::class),
                executor: $app->make(SafeAgentExecutor::class),
                timeoutConfig: $app->make(AgentTimeoutConfig::class),
            );
        });

        // Sprint 8b — EmbeddingService
        $this->app->singleton(EmbeddingServiceInterface::class, function ($app) {
            return new EmbeddingService(
                configResolver: $app->make(AiConfigResolver::class),
            );
        });

        // Sprint 8b — RecommendationService
        $this->app->singleton(RecommendationServiceInterface::class, function ($app) {
            return new RecommendationService(
                embeddingService: $app->make(EmbeddingServiceInterface::class),
                cestaRepo: $app->make(CestaRepositoryInterface::class),
                cotacaoRepo: $app->make(CotacaoRepositoryInterface::class),
                configResolver: $app->make(AiConfigResolver::class),
            );
        });

        // Sprint 8b — PortfolioAnalystAgent
        $this->app->singleton(PortfolioAnalystAgent::class, function ($app) {
            return new PortfolioAnalystAgent(
                cestaRepo: $app->make(CestaRepositoryInterface::class),
                custodiaRepo: $app->make(CustodiaRepositoryInterface::class),
                cotacaoRepo: $app->make(CotacaoRepositoryInterface::class),
                recommendationService: $app->make(RecommendationServiceInterface::class),
            );
        });

        // Sprint 9a — RiskAnalysisService
        $this->app->singleton(RiskAnalysisService::class);

        // Sprint 9a — RiskAnalystAgent
        $this->app->singleton(RiskAnalystAgent::class, function ($app) {
            return new RiskAnalystAgent(
                custodiaRepo: $app->make(CustodiaRepositoryInterface::class),
                cotacaoRepo: $app->make(CotacaoRepositoryInterface::class),
                riskService: $app->make(RiskAnalysisService::class),
                kafkaProducer: $app->make(KafkaProducer::class),
            );
        });

        // Sprint 9a — TaxAnalystAgent
        $this->app->singleton(TaxAnalystAgent::class, function ($app) {
            return new TaxAnalystAgent(
                custodiaRepo: $app->make(CustodiaRepositoryInterface::class),
                cotacaoRepo: $app->make(CotacaoRepositoryInterface::class),
            );
        });

        // Sprint 9a — MarketIntelligenceAgent
        $this->app->singleton(MarketIntelligenceAgent::class, function ($app) {
            return new MarketIntelligenceAgent(
                dataProviderManager: $app->make(DataProviderManager::class),
                custodiaRepo: $app->make(CustodiaRepositoryInterface::class),
                configResolver: $app->make(AiConfigResolver::class),
            );
        });

        // Sprint 9b — SimulatorAgent
        $this->app->singleton(SimulatorAgent::class, function ($app) {
            return new SimulatorAgent(
                cestaRepo: $app->make(CestaRepositoryInterface::class),
                custodiaRepo: $app->make(CustodiaRepositoryInterface::class),
                cotacaoRepo: $app->make(CotacaoRepositoryInterface::class),
            );
        });

        // Sprint 9b — EducatorAgent
        $this->app->singleton(EducatorAgent::class, function ($app) {
            return new EducatorAgent(
                custodiaRepo: $app->make(CustodiaRepositoryInterface::class),
                cestaRepo: $app->make(CestaRepositoryInterface::class),
                cotacaoRepo: $app->make(CotacaoRepositoryInterface::class),
                configResolver: $app->make(AiConfigResolver::class),
            );
        });

        // Sprint 9b — ScopeGuardrail
        $this->app->singleton(ScopeGuardrail::class);

        // Sprint 10 — Notification services
        $this->app->singleton(NotificationPrioritizer::class);

        $this->app->singleton(AgentNotificationDispatcher::class, function ($app) {
            return new AgentNotificationDispatcher(
                prioritizer: $app->make(NotificationPrioritizer::class),
            );
        });

        $this->app->singleton(ProactiveChatInjector::class, function ($app) {
            return new ProactiveChatInjector(
                cache: $app->make(Cache::class),
            );
        });
    }

    public function boot(): void
    {
        // Register event listeners
        Event::listen(
            CotacoesImportadas::class,
            UpdateEmbeddingsOnCotacoesImported::class,
        );

        // Sprint 10 — Event-driven agent triggers
        Event::listen(
            CompraDistribuida::class,
            TriggerAgentsOnCompraDistribuida::class,
        );

        Event::listen(
            RebalanceamentoTipoA::class,
            TriggerAgentsOnRebalanceamento::class,
        );

        Event::listen(
            RebalanceamentoTipoB::class,
            TriggerAgentsOnRebalanceamento::class,
        );

        Event::listen(
            ClienteAderiu::class,
            TriggerEducatorOnClienteAderiu::class,
        );

        Event::listen(
            ValorMensalAlterado::class,
            TriggerSimulatorOnValorMensalAlterado::class,
        );
    }
}
