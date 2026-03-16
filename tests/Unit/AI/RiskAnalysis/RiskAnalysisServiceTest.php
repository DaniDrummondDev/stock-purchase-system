<?php

declare(strict_types=1);

use App\Domain\AI\RiskAnalysis\Services\RiskAnalysisService;
use App\Domain\AI\RiskAnalysis\ValueObjects\PortfolioRiskMetrics;
use App\Domain\AI\RiskAnalysis\ValueObjects\RiskScore;

function makeCustodiaMock(string $ticker, int $quantidade, float $precoMedio): object
{
    $mock = Mockery::mock();
    $mock->shouldReceive('ticker')->andReturn($ticker);
    $mock->shouldReceive('quantidade')->andReturn($quantidade);
    $mock->shouldReceive('precoMedio')->andReturn($precoMedio);

    return $mock;
}

it('analyze with single-ticker portfolio returns high risk score', function () {
    $service = new RiskAnalysisService;

    $custodias = [
        makeCustodiaMock('PETR4', 100, 38.00),
    ];

    $historicalPrices = [
        'PETR4' => [38.0, 37.5, 38.2, 37.8, 38.5, 37.0, 38.1, 37.9, 38.3, 37.6],
    ];

    $result = $service->analyze($custodias, $historicalPrices);

    expect($result)->toBeInstanceOf(RiskScore::class)
        ->and($result->score())->toBeGreaterThan(0.5);
});

it('analyze with 5 equal-weight tickers returns lower risk', function () {
    $service = new RiskAnalysisService;

    $custodias = [
        makeCustodiaMock('PETR4', 100, 20.00),
        makeCustodiaMock('VALE3', 100, 20.00),
        makeCustodiaMock('ITUB4', 100, 20.00),
        makeCustodiaMock('BBDC4', 100, 20.00),
        makeCustodiaMock('ABEV3', 100, 20.00),
    ];

    $historicalPrices = [
        'PETR4' => [20.0, 20.1, 19.9, 20.2, 19.8, 20.0, 20.1, 19.9, 20.0, 20.1],
        'VALE3' => [20.0, 20.1, 19.9, 20.2, 19.8, 20.0, 20.1, 19.9, 20.0, 20.1],
        'ITUB4' => [20.0, 20.1, 19.9, 20.2, 19.8, 20.0, 20.1, 19.9, 20.0, 20.1],
        'BBDC4' => [20.0, 20.1, 19.9, 20.2, 19.8, 20.0, 20.1, 19.9, 20.0, 20.1],
        'ABEV3' => [20.0, 20.1, 19.9, 20.2, 19.8, 20.0, 20.1, 19.9, 20.0, 20.1],
    ];

    $singleTickerResult = $service->analyze(
        [makeCustodiaMock('PETR4', 100, 38.00)],
        ['PETR4' => [38.0, 37.5, 38.2, 37.8, 38.5, 37.0, 38.1, 37.9, 38.3, 37.6]],
    );

    $result = $service->analyze($custodias, $historicalPrices);

    expect($result)->toBeInstanceOf(RiskScore::class)
        ->and($result->score())->toBeLessThan($singleTickerResult->score());
});

it('analyze with empty custodias returns valid score', function () {
    $service = new RiskAnalysisService;

    $result = $service->analyze([], []);

    expect($result)->toBeInstanceOf(RiskScore::class)
        ->and($result->score())->toBeGreaterThanOrEqual(0.0)
        ->and($result->score())->toBeLessThanOrEqual(1.0);
});

it('buildMetrics returns PortfolioRiskMetrics with correct fields', function () {
    $service = new RiskAnalysisService;

    $custodias = [
        makeCustodiaMock('PETR4', 200, 35.00),
        makeCustodiaMock('VALE3', 100, 70.00),
    ];

    $historicalPrices = [
        'PETR4' => [35.0, 34.8, 35.2, 34.9, 35.1, 35.0, 34.7, 35.3, 34.8, 35.0],
        'VALE3' => [70.0, 69.5, 70.5, 69.8, 70.2, 70.0, 69.3, 70.7, 69.6, 70.1],
    ];

    $metrics = $service->buildMetrics($custodias, $historicalPrices);

    expect($metrics)->toBeInstanceOf(PortfolioRiskMetrics::class)
        ->and($metrics->herfindahlIndex)->toBeFloat()
        ->and($metrics->volatility)->toBeFloat()
        ->and($metrics->maxConcentration)->toBeFloat()
        ->and($metrics->tickerCount)->toBe(2)
        ->and($metrics->alerts)->toBeArray()
        ->and($metrics->tickerWeights)->toBeArray()
        ->and($metrics->tickerWeights)->toHaveKeys(['PETR4', 'VALE3']);
});
