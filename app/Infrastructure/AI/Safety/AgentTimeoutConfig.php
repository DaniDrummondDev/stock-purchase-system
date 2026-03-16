<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Safety;

class AgentTimeoutConfig
{
    public function __construct(
        private readonly int $defaultTimeoutSeconds = 30,
        private readonly int $maxParallelAgents = 4,
        private readonly int $planningMaxTokens = 4000,
        private readonly int $consolidationMaxTokens = 8000,
    ) {}

    public function defaultTimeout(): int
    {
        return $this->defaultTimeoutSeconds;
    }

    public function maxParallelAgents(): int
    {
        return $this->maxParallelAgents;
    }

    public function planningMaxTokens(): int
    {
        return $this->planningMaxTokens;
    }

    public function consolidationMaxTokens(): int
    {
        return $this->consolidationMaxTokens;
    }

    public static function fromConfig(): static
    {
        return new static(
            defaultTimeoutSeconds: (int) config('ai.agents.default_timeout', 30),
            maxParallelAgents: (int) config('ai.agents.max_parallel', 4),
            planningMaxTokens: (int) config('ai.agents.token_budget.planning', 4000),
            consolidationMaxTokens: (int) config('ai.agents.token_budget.consolidation', 8000),
        );
    }
}
