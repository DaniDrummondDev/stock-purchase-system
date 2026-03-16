<?php

declare(strict_types=1);

namespace App\Domain\AI\RiskAnalysis\ValueObjects;

enum RiskBand: string
{
    case Conservative = 'conservative';  // 0.0 - 0.3
    case Moderate = 'moderate';          // 0.3 - 0.6
    case Aggressive = 'aggressive';      // 0.6 - 0.8
    case Critical = 'critical';          // 0.8 - 1.0

    public static function fromScore(float $score): self
    {
        return match (true) {
            $score < 0.3 => self::Conservative,
            $score < 0.6 => self::Moderate,
            $score < 0.8 => self::Aggressive,
            default => self::Critical,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Conservative => 'Conservador',
            self::Moderate => 'Moderado',
            self::Aggressive => 'Agressivo',
            self::Critical => 'Crítico',
        };
    }
}
