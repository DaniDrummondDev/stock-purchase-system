<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Infrastructure\AI\Agents\MarketIntelligenceAgent;
use App\Infrastructure\AI\AiConfigResolver;
use App\Infrastructure\AI\DataProviders\DataProviderManager;

it('implements FinanceAgentInterface', function () {
    $agent = new MarketIntelligenceAgent(
        Mockery::mock(DataProviderManager::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(AiConfigResolver::class),
    );

    expect($agent)->toBeInstanceOf(FinanceAgentInterface::class);
});

it('getName returns market_intelligence', function () {
    $agent = new MarketIntelligenceAgent(
        Mockery::mock(DataProviderManager::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(AiConfigResolver::class),
    );

    expect($agent->getName())->toBe('market_intelligence');
});

it('getParameterSchema has action with get_market_context in enum', function () {
    $agent = new MarketIntelligenceAgent(
        Mockery::mock(DataProviderManager::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(AiConfigResolver::class),
    );

    $schema = $agent->getParameterSchema();

    expect($schema['required'])->toContain('action')
        ->and($schema['properties']['action']['type'])->toBe('string')
        ->and($schema['properties']['action']['enum'])->toContain('get_market_context');
});
