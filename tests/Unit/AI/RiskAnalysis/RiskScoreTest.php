<?php

declare(strict_types=1);

use App\Domain\AI\RiskAnalysis\ValueObjects\RiskBand;
use App\Domain\AI\RiskAnalysis\ValueObjects\RiskScore;

it('fromScore 0.5 creates valid instance with Moderate band', function () {
    $score = RiskScore::fromScore(0.5);

    expect($score->score())->toBe(0.5)
        ->and($score->band())->toBe(RiskBand::Moderate);
});

it('fromScore with negative value throws InvalidArgumentException', function () {
    RiskScore::fromScore(-0.1);
})->throws(InvalidArgumentException::class);

it('fromScore above 1.0 throws InvalidArgumentException', function () {
    RiskScore::fromScore(1.1);
})->throws(InvalidArgumentException::class);

it('fromScore 0.0 works (lower boundary)', function () {
    $score = RiskScore::fromScore(0.0);

    expect($score->score())->toBe(0.0)
        ->and($score->band())->toBe(RiskBand::Conservative);
});

it('fromScore 1.0 works (upper boundary)', function () {
    $score = RiskScore::fromScore(1.0);

    expect($score->score())->toBe(1.0)
        ->and($score->band())->toBe(RiskBand::Critical);
});

it('isCritical returns true for score >= 0.8', function () {
    expect(RiskScore::fromScore(0.8)->isCritical())->toBeTrue()
        ->and(RiskScore::fromScore(0.9)->isCritical())->toBeTrue()
        ->and(RiskScore::fromScore(1.0)->isCritical())->toBeTrue()
        ->and(RiskScore::fromScore(0.79)->isCritical())->toBeFalse();
});

it('requiresAlert returns true for score >= 0.7', function () {
    expect(RiskScore::fromScore(0.7)->requiresAlert())->toBeTrue()
        ->and(RiskScore::fromScore(0.8)->requiresAlert())->toBeTrue()
        ->and(RiskScore::fromScore(0.69)->requiresAlert())->toBeFalse();
});
