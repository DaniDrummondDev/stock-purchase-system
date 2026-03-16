<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\AgentResult;

it('creates a valid agent result', function () {
    $result = new AgentResult(
        data: ['portfolio_value' => 50000],
        summary: 'Your portfolio is worth R$50,000',
        confidence: 0.95,
        metadata: ['source' => 'cotahist'],
    );

    expect($result->data)->toBe(['portfolio_value' => 50000])
        ->and($result->summary)->toBe('Your portfolio is worth R$50,000')
        ->and($result->confidence)->toBe(0.95)
        ->and($result->metadata)->toBe(['source' => 'cotahist']);
});

it('accepts zero confidence', function () {
    $result = new AgentResult(data: [], summary: 'No data', confidence: 0.0);

    expect($result->confidence)->toBe(0.0);
});

it('accepts max confidence', function () {
    $result = new AgentResult(data: [], summary: 'Certain', confidence: 1.0);

    expect($result->confidence)->toBe(1.0);
});

it('rejects confidence below zero', function () {
    new AgentResult(data: [], summary: 'Invalid', confidence: -0.1);
})->throws(InvalidArgumentException::class, 'Confidence must be between 0.0 and 1.0');

it('rejects confidence above one', function () {
    new AgentResult(data: [], summary: 'Invalid', confidence: 1.1);
})->throws(InvalidArgumentException::class, 'Confidence must be between 0.0 and 1.0');

it('defaults metadata to empty array', function () {
    $result = new AgentResult(data: [], summary: 'Test', confidence: 0.5);

    expect($result->metadata)->toBe([]);
});
