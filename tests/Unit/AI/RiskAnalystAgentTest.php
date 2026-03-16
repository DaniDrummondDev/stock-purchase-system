<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\AI\Contracts\TriggerType;
use App\Domain\AI\RiskAnalysis\Services\RiskAnalysisService;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\MarketData\Repositories\CotacaoRepositoryInterface;
use App\Infrastructure\AI\Agents\RiskAnalystAgent;
use App\Infrastructure\Kafka\KafkaProducer;

it('implements FinanceAgentInterface', function () {
    $agent = new RiskAnalystAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        new RiskAnalysisService,
        Mockery::mock(KafkaProducer::class),
    );

    expect($agent)->toBeInstanceOf(FinanceAgentInterface::class);
});

it('getName returns risk_analyst', function () {
    $agent = new RiskAnalystAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        new RiskAnalysisService,
        Mockery::mock(KafkaProducer::class),
    );

    expect($agent->getName())->toBe('risk_analyst');
});

it('getParameterSchema has action with correct enum values', function () {
    $agent = new RiskAnalystAgent(
        Mockery::mock(CustodiaRepositoryInterface::class),
        Mockery::mock(CotacaoRepositoryInterface::class),
        new RiskAnalysisService,
        Mockery::mock(KafkaProducer::class),
    );

    $schema = $agent->getParameterSchema();

    expect($schema['required'])->toContain('action')
        ->and($schema['properties']['action']['type'])->toBe('string')
        ->and($schema['properties']['action']['enum'])->toBe([
            'calculate_risk',
            'get_cached_risk',
        ]);
});

it('execute with calculate_risk and empty custodias returns result with confidence', function () {
    $custodiaRepo = Mockery::mock(CustodiaRepositoryInterface::class);
    $custodiaRepo->shouldReceive('findByClienteId')->once()->andReturn([]);

    $agent = new RiskAnalystAgent(
        $custodiaRepo,
        Mockery::mock(CotacaoRepositoryInterface::class),
        new RiskAnalysisService,
        Mockery::mock(KafkaProducer::class),
    );

    $context = new AgentContext(
        clienteId: 'test-uuid',
        request: 'test',
        triggerType: TriggerType::Chat,
        additionalParams: ['action' => 'calculate_risk', 'cliente_id' => 'test-uuid'],
    );

    $result = $agent->execute($context);

    expect($result->confidence)->toBe(1.0)
        ->and($result->summary)->toContain('custódia')
        ->and($result->metadata['action'])->toBe('calculate_risk')
        ->and($result->metadata['agent'])->toBe('risk_analyst');
});
