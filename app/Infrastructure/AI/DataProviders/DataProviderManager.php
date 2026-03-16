<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\DataProviders;

use App\Domain\AI\Contracts\DataQuery;
use App\Domain\AI\Contracts\DataResult;
use App\Domain\AI\DataProviders\DataProviderRegistry;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class DataProviderManager
{
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
            try {
                $result = $provider->query($query);

                if ($query->cacheTtlSeconds !== null) {
                    $this->cache->put($cacheKey, $result, $query->cacheTtlSeconds);
                }

                return $result;
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        return null;
    }

    private function buildCacheKey(DataQuery $query): string
    {
        return 'dp:'.$query->capability.':'.md5(json_encode($query->params));
    }
}
