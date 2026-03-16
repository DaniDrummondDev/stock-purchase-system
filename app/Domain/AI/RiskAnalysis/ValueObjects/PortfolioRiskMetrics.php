<?php

declare(strict_types=1);

namespace App\Domain\AI\RiskAnalysis\ValueObjects;

use InvalidArgumentException;

final readonly class PortfolioRiskMetrics
{
    /**
     * @param  float  $herfindahlIndex  Normalized HHI concentration measure (0.0-1.0)
     * @param  float  $volatility  Annualized standard deviation
     * @param  float  $maxConcentration  Highest single ticker weight (0.0-1.0)
     * @param  int  $tickerCount  Number of distinct tickers
     * @param  array<string>  $alerts  Risk alerts in Portuguese
     * @param  array<string, float>  $tickerWeights  Ticker => weight mapping
     */
    public function __construct(
        public float $herfindahlIndex,
        public float $volatility,
        public float $maxConcentration,
        public int $tickerCount,
        public array $alerts,
        public array $tickerWeights,
    ) {
        if ($herfindahlIndex < 0.0 || $herfindahlIndex > 1.0) {
            throw new InvalidArgumentException(
                "Herfindahl index must be between 0.0 and 1.0, got {$herfindahlIndex}"
            );
        }

        if ($maxConcentration < 0.0 || $maxConcentration > 1.0) {
            throw new InvalidArgumentException(
                "Max concentration must be between 0.0 and 1.0, got {$maxConcentration}"
            );
        }
    }
}
