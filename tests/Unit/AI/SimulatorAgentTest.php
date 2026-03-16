<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\AI\Agents\SimulatorAgent;

it('implements FinanceAgentInterface', function () {
    $agent = new SimulatorAgent(
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
    );

    expect($agent)->toBeInstanceOf(FinanceAgentInterface::class);
});

it('getName returns simulator', function () {
    $agent = new SimulatorAgent(
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
    );

    expect($agent->getName())->toBe('simulator');
});

it('getParameterSchema has action with correct enum values', function () {
    $agent = new SimulatorAgent(
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
    );

    $schema = $agent->getParameterSchema();

    expect($schema['properties']['action']['enum'])
        ->toContain('simulate_aporte_change')
        ->toContain('simulate_ticker_swap')
        ->toContain('project_portfolio');
});

it('getDescription returns non-empty string', function () {
    $agent = new SimulatorAgent(
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
    );

    expect($agent->getDescription())->toBeString()->not->toBeEmpty();
});
