<?php

declare(strict_types=1);

use App\Infrastructure\AI\Safety\ScopeGuardrail;

it('identifies financial messages as in scope', function () {
    $guardrail = new ScopeGuardrail;

    $result = $guardrail->isInScope('Como está minha carteira de ações?');

    expect($result->inScope)->toBeTrue();
});

it('allows short messages', function () {
    $guardrail = new ScopeGuardrail;

    $result = $guardrail->isInScope('Olá!');

    expect($result->inScope)->toBeTrue()
        ->and($result->reason)->toBe('short_message_allowed');
});

it('identifies messages with financial keywords', function () {
    $guardrail = new ScopeGuardrail;

    $result = $guardrail->isInScope('Qual o risco da minha carteira de investimento?');

    expect($result->inScope)->toBeTrue()
        ->and($result->reason)->toBe('financial_keyword_match');
});

it('hasBlockedContent returns null for normal messages', function () {
    $guardrail = new ScopeGuardrail;

    expect($guardrail->hasBlockedContent('Como funciona o preço médio?'))->toBeNull();
});
