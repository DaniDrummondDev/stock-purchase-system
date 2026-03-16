<?php

declare(strict_types=1);

namespace App\Domain\AI\ValueObjects;

final readonly class RecommendationResult
{
    /**
     * @param  array<int, array{ticker: string, percentual: float, similarityScore: float, rationale: string}>  $suggestedTickers
     * @param  array<int, array{ticker: string, percentual: float}>  $currentBasketSummary
     * @param  float  $confidence  Value between 0.0 and 1.0
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public array $suggestedTickers,
        public array $currentBasketSummary,
        public float $confidence,
        public \DateTimeImmutable $generatedAt,
    ) {
        if ($this->confidence < 0.0 || $this->confidence > 1.0) {
            throw new \InvalidArgumentException(
                sprintf('Confidence must be between 0.0 and 1.0, got %.4f', $this->confidence)
            );
        }
    }
}
