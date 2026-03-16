<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Services;

use App\Domain\AI\Contracts\EmbeddingServiceInterface;
use App\Infrastructure\AI\AiConfigResolver;
use Laravel\Ai\Embeddings;

final class EmbeddingService implements EmbeddingServiceInterface
{
    public function __construct(
        private readonly AiConfigResolver $configResolver,
    ) {}

    /**
     * @return float[] Vector of dimensions defined by the provider (default 1024 for Voyage AI)
     */
    public function embed(string $text): array
    {
        $provider = $this->configResolver->resolveProviderName('embeddings');

        $response = Embeddings::for([$text])
            ->cache(seconds: 86400)
            ->generate($provider);

        return $response->first();
    }

    /**
     * @param  string[]  $texts
     * @return float[][] Array of vectors
     */
    public function embedBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $provider = $this->configResolver->resolveProviderName('embeddings');

        // Process in chunks of 20 to respect API rate limits
        $allEmbeddings = [];
        $chunks = array_chunk($texts, 20);

        foreach ($chunks as $chunk) {
            $response = Embeddings::for($chunk)
                ->generate($provider);

            foreach ($response->embeddings as $embedding) {
                $allEmbeddings[] = $embedding;
            }
        }

        return $allEmbeddings;
    }
}
