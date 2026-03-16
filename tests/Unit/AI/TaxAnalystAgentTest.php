<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\AgentResult;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\AI\Contracts\TriggerType;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\AI\Agents\TaxAnalystAgent;

it('implements FinanceAgentInterface', function () {
    $agent = new TaxAnalystAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
    );

    expect($agent)->toBeInstanceOf(FinanceAgentInterface::class);
});

it('getName returns tax_analyst', function () {
    $agent = new TaxAnalystAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
    );

    expect($agent->getName())->toBe('tax_analyst');
});

it('getParameterSchema has action with correct enum values', function () {
    $agent = new TaxAnalystAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
    );

    $schema = $agent->getParameterSchema();

    expect($schema['required'])->toContain('action')
        ->and($schema['properties']['action']['type'])->toBe('string')
        ->and($schema['properties']['action']['enum'])->toBe([
            'analyze_tax_status',
            'simulate_sale_tax',
        ]);
});

it('execute with analyze_tax_status and mocked repos returns result', function () {
    $agent = new TaxAnalystAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
    );

    $context = new AgentContext(
        clienteId: 'test-uuid',
        request: 'test',
        triggerType: TriggerType::Chat,
        additionalParams: ['action' => 'analyze_tax_status', 'cliente_id' => 'test-uuid'],
    );

    $result = $agent->execute($context);

    expect($result)->toBeInstanceOf(AgentResult::class)
        ->and($result->confidence)->toBeGreaterThanOrEqual(0.0)
        ->and($result->confidence)->toBeLessThanOrEqual(1.0);
});
