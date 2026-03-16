<?php

declare(strict_types=1);

use App\Domain\AI\ValueObjects\PortfolioAnalysis;

it('constructs with valid data', function () {
    $composition = [
        ['ticker' => 'PETR4', 'targetPercentual' => 30.0, 'actualPercentual' => 28.5, 'deviationPp' => -1.5],
        ['ticker' => 'VALE3', 'targetPercentual' => 25.0, 'actualPercentual' => 26.0, 'deviationPp' => 1.0],
    ];

    $estimatedPL = [
        [
            'ticker' => 'PETR4',
            'quantidade' => 100,
            'precoMedio' => 32.00,
            'cotacaoAtual' => 35.50,
            'custoTotal' => 3200.00,
            'valorAtual' => 3550.00,
            'lucroOuPrejuizo' => 350.00,
            'percentual' => 10.94,
        ],
    ];

    $analysis = new PortfolioAnalysis(
        composition: $composition,
        estimatedPL: $estimatedPL,
        totalCusto: 3200.00,
        totalValorAtual: 3550.00,
        totalPL: 350.00,
        totalPLPercentual: 10.94,
    );

    expect($analysis->composition)->toHaveCount(2)
        ->and($analysis->estimatedPL)->toHaveCount(1)
        ->and($analysis->totalCusto)->toBe(3200.00)
        ->and($analysis->totalValorAtual)->toBe(3550.00)
        ->and($analysis->totalPL)->toBe(350.00)
        ->and($analysis->totalPLPercentual)->toBe(10.94);
});

it('has all readonly properties accessible', function () {
    $analysis = new PortfolioAnalysis(
        composition: [],
        estimatedPL: [],
        totalCusto: 0.0,
        totalValorAtual: 0.0,
        totalPL: 0.0,
        totalPLPercentual: 0.0,
    );

    $reflection = new ReflectionClass($analysis);

    expect($reflection->isReadOnly())->toBeTrue();

    foreach ($reflection->getProperties() as $property) {
        expect($property->isReadOnly())->toBeTrue()
            ->and($property->isPublic())->toBeTrue();
    }
});

it('constructs with empty arrays', function () {
    $analysis = new PortfolioAnalysis(
        composition: [],
        estimatedPL: [],
        totalCusto: 0.0,
        totalValorAtual: 0.0,
        totalPL: 0.0,
        totalPLPercentual: 0.0,
    );

    expect($analysis->composition)->toBeEmpty()
        ->and($analysis->estimatedPL)->toBeEmpty()
        ->and($analysis->totalCusto)->toBe(0.0)
        ->and($analysis->totalValorAtual)->toBe(0.0)
        ->and($analysis->totalPL)->toBe(0.0)
        ->and($analysis->totalPLPercentual)->toBe(0.0);
});
