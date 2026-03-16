<?php

declare(strict_types=1);

namespace App\Domain\AI\RiskAnalysis\Services;

use App\Domain\AI\RiskAnalysis\ValueObjects\PortfolioRiskMetrics;
use App\Domain\AI\RiskAnalysis\ValueObjects\RiskScore;

final class RiskAnalysisService
{
    /**
     * Analyze portfolio risk from custodias and historical prices.
     *
     * @param  array<object>  $custodias  Objects with ->ticker(), ->quantidade(), ->precoMedio()
     * @param  array<string, array<float>>  $historicalPrices  Ticker => closing prices (newest first)
     */
    public function analyze(array $custodias, array $historicalPrices): RiskScore
    {
        $metrics = $this->buildMetrics($custodias, $historicalPrices);

        $hhiNorm = $metrics->herfindahlIndex;
        $volNorm = $this->normalizeVolatility($metrics->volatility);
        $concNorm = $this->normalizeConcentration($metrics->maxConcentration);

        $finalScore = (0.40 * $hhiNorm) + (0.35 * $volNorm) + (0.25 * $concNorm);
        $finalScore = max(0.0, min(1.0, $finalScore));

        return RiskScore::fromScore(round($finalScore, 4));
    }

    /**
     * Build detailed portfolio risk metrics.
     *
     * @param  array<object>  $custodias  Objects with ->ticker(), ->quantidade(), ->precoMedio()
     * @param  array<string, array<float>>  $historicalPrices  Ticker => closing prices (newest first)
     */
    public function buildMetrics(array $custodias, array $historicalPrices): PortfolioRiskMetrics
    {
        $weights = $this->calculateWeights($custodias);
        $tickerCount = count($weights);

        $hhi = $this->calculateHerfindahlIndex($weights);
        $hhiNorm = $this->normalizeHHI($hhi, $tickerCount);

        $volatility = $this->calculatePortfolioVolatility($weights, $historicalPrices);
        $volNorm = $this->normalizeVolatility($volatility);

        $maxConc = $this->calculateMaxConcentration($weights);
        $concNorm = $this->normalizeConcentration($maxConc);

        $alerts = $this->generateAlerts($hhiNorm, $hhi, $maxConc, $volNorm, $volatility, $tickerCount, $weights);

        return new PortfolioRiskMetrics(
            herfindahlIndex: round($hhiNorm, 4),
            volatility: round($volatility, 4),
            maxConcentration: round($maxConc, 4),
            tickerCount: $tickerCount,
            alerts: $alerts,
            tickerWeights: array_map(fn (float $w) => round($w, 4), $weights),
        );
    }

    /**
     * Calculate ticker weights from custodias.
     *
     * @return array<string, float> ticker => weight
     */
    private function calculateWeights(array $custodias): array
    {
        $totalValue = 0.0;
        $tickerValues = [];

        foreach ($custodias as $custodia) {
            $value = $custodia->quantidade() * $custodia->precoMedio();
            $ticker = $custodia->ticker();
            $tickerValues[$ticker] = ($tickerValues[$ticker] ?? 0.0) + $value;
            $totalValue += $value;
        }

        if ($totalValue <= 0.0) {
            return [];
        }

        $weights = [];
        foreach ($tickerValues as $ticker => $value) {
            $weights[$ticker] = $value / $totalValue;
        }

        return $weights;
    }

    /**
     * Raw Herfindahl-Hirschman Index: sum of squared weights.
     */
    private function calculateHerfindahlIndex(array $weights): float
    {
        $hhi = 0.0;
        foreach ($weights as $weight) {
            $hhi += $weight ** 2;
        }

        return $hhi;
    }

    /**
     * Normalize HHI: (HHI - 1/N) / (1 - 1/N), clamped to [0.0, 1.0].
     */
    private function normalizeHHI(float $hhi, int $n): float
    {
        if ($n <= 1) {
            return 1.0;
        }

        $minHHI = 1.0 / $n;
        $normalized = ($hhi - $minHHI) / (1.0 - $minHHI);

        return max(0.0, min(1.0, $normalized));
    }

    /**
     * Calculate annualized portfolio volatility (simplified weighted average).
     *
     * @param  array<string, float>  $weights
     * @param  array<string, array<float>>  $historicalPrices
     */
    private function calculatePortfolioVolatility(array $weights, array $historicalPrices): float
    {
        $weightedVol = 0.0;
        $tradingDaysPerYear = 252;

        foreach ($weights as $ticker => $weight) {
            $prices = $historicalPrices[$ticker] ?? [];

            if (count($prices) < 2) {
                continue;
            }

            $returns = $this->calculateDailyReturns($prices);

            if (count($returns) === 0) {
                continue;
            }

            $dailyStdDev = $this->standardDeviation($returns);
            $annualizedVol = $dailyStdDev * sqrt($tradingDaysPerYear);

            $weightedVol += $weight * $annualizedVol;
        }

        return $weightedVol;
    }

    /**
     * Calculate daily log returns from prices (newest first).
     *
     * @param  array<float>  $prices  Closing prices, newest first
     * @return array<float>
     */
    private function calculateDailyReturns(array $prices): array
    {
        $returns = [];

        for ($i = 0; $i < count($prices) - 1; $i++) {
            $current = $prices[$i];
            $previous = $prices[$i + 1];

            if ($previous > 0.0 && $current > 0.0) {
                $returns[] = log($current / $previous);
            }
        }

        return $returns;
    }

    /**
     * Population standard deviation.
     *
     * @param  array<float>  $values
     */
    private function standardDeviation(array $values): float
    {
        $n = count($values);

        if ($n === 0) {
            return 0.0;
        }

        $mean = array_sum($values) / $n;
        $squaredDiffs = 0.0;

        foreach ($values as $value) {
            $squaredDiffs += ($value - $mean) ** 2;
        }

        return sqrt($squaredDiffs / $n);
    }

    /**
     * Normalize volatility against 50% annual benchmark.
     */
    private function normalizeVolatility(float $volatility): float
    {
        return min($volatility / 0.50, 1.0);
    }

    /**
     * Get maximum single-ticker weight.
     */
    private function calculateMaxConcentration(array $weights): float
    {
        if (empty($weights)) {
            return 0.0;
        }

        return max($weights);
    }

    /**
     * Normalize concentration: (maxConc - 0.2) / 0.6, clamped to [0.0, 1.0].
     */
    private function normalizeConcentration(float $maxConc): float
    {
        return max(0.0, min(1.0, ($maxConc - 0.2) / 0.6));
    }

    /**
     * Generate risk alerts in Portuguese.
     *
     * @return array<string>
     */
    private function generateAlerts(
        float $hhiNorm,
        float $rawHHI,
        float $maxConc,
        float $volNorm,
        float $volatility,
        int $tickerCount,
        array $weights,
    ): array {
        $alerts = [];

        if ($hhiNorm > 0.5) {
            $hhiFormatted = number_format($rawHHI, 2, ',', '.');
            $alerts[] = "Carteira muito concentrada — {$tickerCount} ativos com HHI de {$hhiFormatted}";
        }

        if ($maxConc > 0.4) {
            $topTicker = array_keys($weights, max($weights), true)[0] ?? '???';
            $pct = number_format($maxConc * 100, 1, ',', '.');
            $alerts[] = "Ativo {$topTicker} representa {$pct}% da carteira";
        }

        if ($volNorm > 0.7) {
            $volPct = number_format($volatility * 100, 1, ',', '.');
            $alerts[] = "Volatilidade alta: {$volPct}% ao ano";
        }

        if ($tickerCount < 3) {
            $alerts[] = "Diversificação baixa: apenas {$tickerCount} ativos";
        }

        return $alerts;
    }
}
