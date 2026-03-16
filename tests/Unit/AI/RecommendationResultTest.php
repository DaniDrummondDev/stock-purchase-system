<?php

declare(strict_types=1);

use App\Domain\AI\ValueObjects\RecommendationResult;

it('constructs with valid data', function () {
    $result = new RecommendationResult(
        suggestedTickers: [
            ['ticker' => 'PETR4', 'percentual' => 30.0, 'similarityScore' => 0.95, 'rationale' => 'Alta liquidez'],
            ['ticker' => 'VALE3', 'percentual' => 25.0, 'similarityScore' => 0.90, 'rationale' => 'Forte desempenho'],
        ],
        currentBasketSummary: [
            ['ticker' => 'ITUB4', 'percentual' => 20.0],
        ],
        confidence: 0.85,
        generatedAt: new DateTimeImmutable('2026-03-15T10:00:00+00:00'),
    );

    expect($result->suggestedTickers)->toHaveCount(2)
        ->and($result->currentBasketSummary)->toHaveCount(1)
        ->and($result->confidence)->toBe(0.85)
        ->and($result->generatedAt->format('Y-m-d'))->toBe('2026-03-15');
});

it('throws InvalidArgumentException when confidence is above 1.0', function () {
    new RecommendationResult(
        suggestedTickers: [],
        currentBasketSummary: [],
        confidence: 1.5,
        generatedAt: new DateTimeImmutable,
    );
})->throws(InvalidArgumentException::class);

it('throws InvalidArgumentException when confidence is below 0.0', function () {
    new RecommendationResult(
        suggestedTickers: [],
        currentBasketSummary: [],
        confidence: -0.1,
        generatedAt: new DateTimeImmutable,
    );
})->throws(InvalidArgumentException::class);

it('accepts boundary confidence values 0.0 and 1.0', function () {
    $resultZero = new RecommendationResult(
        suggestedTickers: [],
        currentBasketSummary: [],
        confidence: 0.0,
        generatedAt: new DateTimeImmutable,
    );

    $resultOne = new RecommendationResult(
        suggestedTickers: [],
        currentBasketSummary: [],
        confidence: 1.0,
        generatedAt: new DateTimeImmutable,
    );

    expect($resultZero->confidence)->toBe(0.0)
        ->and($resultOne->confidence)->toBe(1.0);
});

it('constructs with empty suggestedTickers array', function () {
    $result = new RecommendationResult(
        suggestedTickers: [],
        currentBasketSummary: [
            ['ticker' => 'PETR4', 'percentual' => 30.0],
        ],
        confidence: 0.0,
        generatedAt: new DateTimeImmutable,
    );

    expect($result->suggestedTickers)->toBeEmpty()
        ->and($result->suggestedTickers)->toBeArray();
});

it('has readonly properties', function () {
    $result = new RecommendationResult(
        suggestedTickers: [],
        currentBasketSummary: [],
        confidence: 0.5,
        generatedAt: new DateTimeImmutable,
    );

    $reflection = new ReflectionClass($result);

    expect($reflection->isReadOnly())->toBeTrue();

    foreach ($reflection->getProperties() as $property) {
        expect($property->isReadOnly())->toBeTrue();
    }
});
