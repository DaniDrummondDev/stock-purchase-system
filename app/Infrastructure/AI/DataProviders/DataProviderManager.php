<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\DataProviders;

use App\Domain\AI\Contracts\DataProviderInterface;
use App\Domain\AI\Contracts\DataQuery;
use App\Domain\AI\Contracts\DataResult;
use App\Domain\AI\DataProviders\DataProviderRegistry;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;

class DataProviderManager
{
    private const MAX_RETRIES = 3;

    public function __construct(
        private readonly DataProviderRegistry $registry,
        private readonly CacheRepository $cache,
    ) {}

    /**
     * Query the first available provider for the requested capability.
     *
     * @throws \RuntimeException If no available provider can fulfil the query.
     */
    public function query(DataQuery $query): DataResult
    {
        $result = $this->resolve($query);

        if ($result === null) {
            throw new \RuntimeException(
                "No available data provider could fulfil capability '{$query->capability}'."
            );
        }

        return $result;
    }

    /**
     * Same as query() but returns null instead of throwing on failure.
     */
    public function queryWithFallback(DataQuery $query): ?DataResult
    {
        return $this->resolve($query);
    }

    private function resolve(DataQuery $query): ?DataResult
    {
        $providers = $this->registry->byCapability($query->capability);
        $available = array_filter(
            $providers,
            fn ($provider): bool => $provider->isAvailable(),
        );

        if ($available === []) {
            return null;
        }

        $cacheKey = $this->buildCacheKey($query);

        if ($query->cacheTtlSeconds !== null) {
            $cached = $this->cache->get($cacheKey);

            if ($cached instanceof DataResult) {
                return new DataResult(
                    data: $cached->data,
                    providerName: $cached->providerName,
                    fromCache: true,
                    fetchedAt: $cached->fetchedAt,
                );
            }
        }

        $lastException = null;

        foreach ($available as $provider) {
            if ($this->isRateLimited($provider)) {
                Log::info("DataProviderManager: Provider {$provider->getName()} is rate limited, skipping.");

                continue;
            }

            $result = $this->queryWithRetry($provider, $query);

            if ($result !== null) {
                if ($query->cacheTtlSeconds !== null) {
                    $this->cache->put($cacheKey, $result, $query->cacheTtlSeconds);
                }

                return $result;
            }
        }

        return null;
    }

    /**
     * Query a provider with exponential backoff retry.
     */
    private function queryWithRetry(DataProviderInterface $provider, DataQuery $query): ?DataResult
    {
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $result = $provider->query($query);
                $this->recordProviderSuccess($provider);

                return $result;
            } catch (\Throwable $e) {
                Log::warning("DataProviderManager: {$provider->getName()} attempt {$attempt}/".self::MAX_RETRIES." failed: {$e->getMessage()}");

                if ($attempt < self::MAX_RETRIES) {
                    usleep((int) (pow(2, $attempt - 1) * 500_000)); // 0.5s, 1s, 2s
                }
            }
        }

        $this->recordProviderFailure($provider);

        return null;
    }

    /**
     * Check if a provider is rate limited (sliding window in Redis).
     */
    private function isRateLimited(DataProviderInterface $provider): bool
    {
        $key = "dp_ratelimit:{$provider->getName()}";
        $count = (int) $this->cache->get($key, 0);
        $limit = (int) config("ai.data_providers.rate_limits.{$provider->getName()}", 60);

        return $count >= $limit;
    }

    private function recordProviderSuccess(DataProviderInterface $provider): void
    {
        $key = "dp_ratelimit:{$provider->getName()}";
        $count = (int) $this->cache->get($key, 0);
        $this->cache->put($key, $count + 1, 3600); // 1 hour window
    }

    private function recordProviderFailure(DataProviderInterface $provider): void
    {
        $failKey = "dp_failures:{$provider->getName()}";
        $failures = (int) $this->cache->get($failKey, 0);
        $this->cache->put($failKey, $failures + 1, 600); // 10 min window
    }

    private function buildCacheKey(DataQuery $query): string
    {
        return 'dp:'.$query->capability.':'.md5(json_encode($query->params));
    }
}
