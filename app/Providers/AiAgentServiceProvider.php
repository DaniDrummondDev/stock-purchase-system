<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\AI\DataProviders\DataProviderRegistry;
use App\Domain\AI\Orchestrator\OrchestratorPromptBuilder;
use App\Infrastructure\AI\AiConfigResolver;
use App\Infrastructure\AI\DataProviders\CotahistProvider;
use App\Infrastructure\AI\DataProviders\DataProviderManager;
use App\Infrastructure\AI\Orchestrator\AgentOrchestrator;
use App\Infrastructure\AI\Safety\AgentCircuitBreaker;
use App\Infrastructure\AI\Safety\AgentTimeoutConfig;
use App\Infrastructure\AI\Safety\SafeAgentExecutor;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\ServiceProvider;

class AiAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataProviderRegistry::class, function () {
            $registry = new DataProviderRegistry;

            $registry->register($this->app->make(CotahistProvider::class));

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
    }
}
