<?php

declare(strict_types=1);

use App\Infrastructure\AI\Safety\AgentCircuitBreaker;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('starts with circuit closed', function () {
    $breaker = new AgentCircuitBreaker(Cache::store(), failureThreshold: 3, cooldownSeconds: 60);

    expect($breaker->isOpen('test_agent'))->toBeFalse()
        ->and($breaker->getFailureCount('test_agent'))->toBe(0);
});

it('opens circuit after threshold failures', function () {
    $breaker = new AgentCircuitBreaker(Cache::store(), failureThreshold: 3, cooldownSeconds: 60);

    $breaker->recordFailure('test_agent');
    $breaker->recordFailure('test_agent');

    expect($breaker->isOpen('test_agent'))->toBeFalse();

    $breaker->recordFailure('test_agent');

    expect($breaker->isOpen('test_agent'))->toBeTrue()
        ->and($breaker->getFailureCount('test_agent'))->toBe(3);
});

it('resets after success', function () {
    $breaker = new AgentCircuitBreaker(Cache::store(), failureThreshold: 3, cooldownSeconds: 60);

    $breaker->recordFailure('test_agent');
    $breaker->recordFailure('test_agent');
    $breaker->recordSuccess('test_agent');

    expect($breaker->isOpen('test_agent'))->toBeFalse()
        ->and($breaker->getFailureCount('test_agent'))->toBe(0);
});

it('can be manually reset', function () {
    $breaker = new AgentCircuitBreaker(Cache::store(), failureThreshold: 2, cooldownSeconds: 60);

    $breaker->recordFailure('test_agent');
    $breaker->recordFailure('test_agent');

    expect($breaker->isOpen('test_agent'))->toBeTrue();

    $breaker->reset('test_agent');

    expect($breaker->isOpen('test_agent'))->toBeFalse();
});

it('tracks failures independently per agent', function () {
    $breaker = new AgentCircuitBreaker(Cache::store(), failureThreshold: 2, cooldownSeconds: 60);

    $breaker->recordFailure('agent_a');
    $breaker->recordFailure('agent_a');
    $breaker->recordFailure('agent_b');

    expect($breaker->isOpen('agent_a'))->toBeTrue()
        ->and($breaker->isOpen('agent_b'))->toBeFalse();
});
