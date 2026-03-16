<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\TriggerType;

it('creates an agent context with required fields', function () {
    $context = new AgentContext(
        clienteId: 'uuid-123',
        request: 'How is my portfolio?',
        triggerType: TriggerType::Chat,
    );

    expect($context->clienteId)->toBe('uuid-123')
        ->and($context->request)->toBe('How is my portfolio?')
        ->and($context->triggerType)->toBe(TriggerType::Chat)
        ->and($context->chatSessionId)->toBeNull()
        ->and($context->additionalParams)->toBe([]);
});

it('creates an agent context with all fields', function () {
    $context = new AgentContext(
        clienteId: 'uuid-456',
        request: 'Simulate increase',
        triggerType: TriggerType::Scheduled,
        chatSessionId: 'session-789',
        additionalParams: ['months' => 12],
    );

    expect($context->chatSessionId)->toBe('session-789')
        ->and($context->additionalParams)->toBe(['months' => 12])
        ->and($context->triggerType)->toBe(TriggerType::Scheduled);
});

it('has immutable properties', function () {
    $context = new AgentContext(
        clienteId: 'uuid-123',
        request: 'test',
        triggerType: TriggerType::Event,
    );

    $reflection = new ReflectionClass($context);

    foreach ($reflection->getProperties() as $property) {
        expect($property->isReadOnly())->toBeTrue();
    }
});
