<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\AI\Agents\EducatorAgent;
use App\Infrastructure\AI\AiConfigResolver;

it('implements FinanceAgentInterface', function () {
    $agent = new EducatorAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(AiConfigResolver::class),
    );

    expect($agent)->toBeInstanceOf(FinanceAgentInterface::class);
});

it('getName returns educator', function () {
    $agent = new EducatorAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(AiConfigResolver::class),
    );

    expect($agent->getName())->toBe('educator');
});

it('getParameterSchema has action with explain_concept in enum', function () {
    $agent = new EducatorAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(AiConfigResolver::class),
    );

    $schema = $agent->getParameterSchema();

    expect($schema['properties']['action']['enum'])
        ->toContain('explain_concept');
});

it('getParameterSchema has concept as required field', function () {
    $agent = new EducatorAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(AiConfigResolver::class),
    );

    $schema = $agent->getParameterSchema();

    expect($schema['required'])->toContain('concept');
});

it('getDescription returns non-empty string', function () {
    $agent = new EducatorAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(AiConfigResolver::class),
    );

    expect($agent->getDescription())->toBeString()->not->toBeEmpty();
});
