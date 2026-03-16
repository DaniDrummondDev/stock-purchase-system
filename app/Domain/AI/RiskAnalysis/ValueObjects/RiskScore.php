<?php

declare(strict_types=1);

namespace App\Domain\AI\RiskAnalysis\ValueObjects;

use InvalidArgumentException;

final readonly class RiskScore
{
    public function __construct(
        private float $score,
        private RiskBand $band,
    ) {
        if ($score < 0.0 || $score > 1.0) {
            throw new InvalidArgumentException(
                "Risk score must be between 0.0 and 1.0, got {$score}"
            );
        }
    }

    public static function fromScore(float $score): self
    {
        return new self(
            score: $score,
            band: RiskBand::fromScore($score),
        );
    }

    public function score(): float
    {
        return $this->score;
    }

    public function band(): RiskBand
    {
        return $this->band;
    }

    public function isCritical(): bool
    {
        return $this->score >= 0.8;
    }

    public function requiresAlert(): bool
    {
        return $this->score >= 0.7;
    }
}
