<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

interface EmbeddingServiceInterface
{
    /**
     * Generate an embedding vector for the given text.
     *
     * @return float[] Vector of 1024 dimensions
     */
    public function embed(string $text): array;

    /**
     * Generate embedding vectors for multiple texts.
     *
     * @param  string[]  $texts
     * @return float[][] Array of vectors
     */
    public function embedBatch(array $texts): array;
}
