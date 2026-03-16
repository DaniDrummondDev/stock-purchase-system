<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Safety;

use Illuminate\Contracts\Cache\Repository as Cache;

class AgentCircuitBreaker
{
    private const PREFIX = 'agent_cb:';

    public function __construct(
        private readonly Cache $cache,
        private readonly int $failureThreshold = 3,
        private readonly int $cooldownSeconds = 600,
    ) {}

    public function isOpen(string $agentName): bool
    {
        return $this->cache->has(self::PREFIX.$agentName.':open');
    }

    public function recordSuccess(string $agentName): void
    {
        $this->cache->forget(self::PREFIX.$agentName.':failures');
        $this->cache->forget(self::PREFIX.$agentName.':open');
    }

    public function recordFailure(string $agentName): void
    {
        $key = self::PREFIX.$agentName.':failures';

        $failures = (int) $this->cache->get($key, 0);
        $failures++;

        $this->cache->put($key, $failures, $this->cooldownSeconds);

        if ($failures >= $this->failureThreshold) {
            $this->cache->put(
                self::PREFIX.$agentName.':open',
                true,
                $this->cooldownSeconds,
            );
        }
    }

    public function reset(string $agentName): void
    {
        $this->cache->forget(self::PREFIX.$agentName.':failures');
        $this->cache->forget(self::PREFIX.$agentName.':open');
    }

    public function getFailureCount(string $agentName): int
    {
        return (int) $this->cache->get(self::PREFIX.$agentName.':failures', 0);
    }
}
