<?php

use App\Domain\Client\ValueObjects\Money;

test('cria money de decimal', function () {
    $money = Money::fromDecimal(100.50);
    expect($money->cents())->toBe(10050)
        ->and($money->toDecimal())->toBe(100.50)
        ->and($money->toDecimalString())->toBe('100.50');
});

test('cria money de centavos', function () {
    $money = Money::fromCents(10050);
    expect($money->toDecimal())->toBe(100.50);
});

test('cria money zero', function () {
    $money = Money::zero();
    expect($money->cents())->toBe(0)
        ->and($money->isZero())->toBeTrue();
});

test('rejeita valor negativo', function () {
    Money::fromDecimal(-10);
})->throws(InvalidArgumentException::class);

test('soma dois valores', function () {
    $a = Money::fromDecimal(100);
    $b = Money::fromDecimal(50.25);
    expect($a->add($b)->toDecimalString())->toBe('150.25');
});

test('subtrai dois valores', function () {
    $a = Money::fromDecimal(100);
    $b = Money::fromDecimal(30);
    expect($a->subtract($b)->toDecimalString())->toBe('70.00');
});

test('multiplica por fator', function () {
    $money = Money::fromDecimal(100);
    expect($money->multiply(0.20)->toDecimalString())->toBe('20.00');
});

test('divide por inteiro usando floor', function () {
    $money = Money::fromDecimal(100);
    $result = $money->divideBy(3);
    expect($result->toDecimalString())->toBe('33.33');
});

test('divisao por zero lanca excecao', function () {
    Money::fromDecimal(100)->divideBy(0);
})->throws(InvalidArgumentException::class);

test('compara valores', function () {
    $a = Money::fromDecimal(200);
    $b = Money::fromDecimal(100);
    expect($a->isGreaterThan($b))->toBeTrue()
        ->and($b->isGreaterThan($a))->toBeFalse()
        ->and($a->isGreaterThanOrEqual($a))->toBeTrue();
});
