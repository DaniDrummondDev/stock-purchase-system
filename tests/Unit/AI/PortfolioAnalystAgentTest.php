<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\AI\Contracts\RecommendationServiceInterface;
use App\Domain\AI\Contracts\TriggerType;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\AI\Agents\PortfolioAnalystAgent;

it('implements FinanceAgentInterface', function () {
    $agent = new PortfolioAnalystAgent(
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(RecommendationServiceInterface::class),
    );

    expect($agent)->toBeInstanceOf(FinanceAgentInterface::class);
});

it('getName returns portfolio_analyst', function () {
    $agent = new PortfolioAnalystAgent(
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(RecommendationServiceInterface::class),
    );

    expect($agent->getName())->toBe('portfolio_analyst');
});

it('getDescription returns a non-empty string', function () {
    $agent = new PortfolioAnalystAgent(
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(RecommendationServiceInterface::class),
    );

    expect($agent->getDescription())->toBeString()
        ->and($agent->getDescription())->not->toBeEmpty();
});

it('getParameterSchema has action as required property with correct enum values', function () {
    $agent = new PortfolioAnalystAgent(
        Mockery::mock(CestaRepositoryInterface::class),
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(RecommendationServiceInterface::class),
    );

    $schema = $agent->getParameterSchema();

    expect($schema['required'])->toContain('action')
        ->and($schema['properties']['action']['type'])->toBe('string')
        ->and($schema['properties']['action']['enum'])->toBe([
            'analyze_composition',
            'estimate_pl',
            'recommend_basket',
        ]);
});

it('execute with analyze_composition returns confidence 0.0 when no active cesta', function () {
    $cestaRepo = Mockery::mock(CestaRepositoryInterface::class);
    $cestaRepo->shouldReceive('findAtiva')->once()->andReturn(null);

    $custodiaRepo = Mockery::mock(CustodiaRepositoryInterface::class);
    $custodiaRepo->shouldReceive('findByClienteId')->once()->andReturn([]);

    $agent = new PortfolioAnalystAgent(
        $cestaRepo,
        $custodiaRepo,
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(RecommendationServiceInterface::class),
    );

    $context = new AgentContext(
        clienteId: 'test-uuid',
        request: 'test',
        triggerType: TriggerType::Chat,
        additionalParams: ['action' => 'analyze_composition'],
    );

    $result = $agent->execute($context);

    expect($result->confidence)->toBe(0.0)
        ->and($result->metadata['action'])->toBe('analyze_composition')
        ->and($result->metadata['agent'])->toBe('portfolio_analyst');
});

it('execute with estimate_pl returns summary about no custodia when empty', function () {
    $custodiaRepo = Mockery::mock(CustodiaRepositoryInterface::class);
    $custodiaRepo->shouldReceive('findByClienteId')->once()->andReturn([]);

    $agent = new PortfolioAnalystAgent(
        Mockery::mock(CestaRepositoryInterface::class),
        $custodiaRepo,
        Mockery::mock(CotacaoRepositoryInterface::class),
        Mockery::mock(RecommendationServiceInterface::class),
    );

    $context = new AgentContext(
        clienteId: 'test-uuid',
        request: 'test',
        triggerType: TriggerType::Chat,
        additionalParams: ['action' => 'estimate_pl'],
    );

    $result = $agent->execute($context);

    expect($result->summary)->toContain('custódia')
        ->and($result->confidence)->toBe(1.0)
        ->and($result->metadata['action'])->toBe('estimate_pl');
});
