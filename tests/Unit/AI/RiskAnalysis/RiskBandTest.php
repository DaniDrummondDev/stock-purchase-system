<?php

declare(strict_types=1);

use App\Domain\AI\RiskAnalysis\ValueObjects\RiskBand;

it('fromScore 0.1 returns Conservative', function () {
    expect(RiskBand::fromScore(0.1))->toBe(RiskBand::Conservative);
});

it('fromScore 0.4 returns Moderate', function () {
    expect(RiskBand::fromScore(0.4))->toBe(RiskBand::Moderate);
});

it('fromScore 0.7 returns Aggressive', function () {
    expect(RiskBand::fromScore(0.7))->toBe(RiskBand::Aggressive);
});

it('fromScore 0.9 returns Critical', function () {
    expect(RiskBand::fromScore(0.9))->toBe(RiskBand::Critical);
});

it('fromScore 0.0 returns Conservative (boundary)', function () {
    expect(RiskBand::fromScore(0.0))->toBe(RiskBand::Conservative);
});

it('fromScore 0.3 returns Moderate (boundary)', function () {
    expect(RiskBand::fromScore(0.3))->toBe(RiskBand::Moderate);
});

it('label returns Portuguese strings', function () {
    expect(RiskBand::Conservative->label())->toBe('Conservador')
        ->and(RiskBand::Moderate->label())->toBe('Moderado')
        ->and(RiskBand::Aggressive->label())->toBe('Agressivo')
        ->and(RiskBand::Critical->label())->toBe('Crítico');
});
