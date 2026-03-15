<?php

use App\Domain\Basket\ValueObjects\Percentual;

test('cria percentual valido', function () {
    $p = new Percentual(30.0);
    expect($p->value())->toBe(30.0);
    expect($p->toDecimalString())->toBe('30.00');
});

test('cria percentual com valor minimo', function () {
    $p = new Percentual(0.01);
    expect($p->value())->toBe(0.01);
});

test('cria percentual de 100', function () {
    $p = new Percentual(100.0);
    expect($p->value())->toBe(100.0);
});

test('rejeita percentual zero', function () {
    new Percentual(0);
})->throws(InvalidArgumentException::class, 'maior que 0');

test('rejeita percentual negativo', function () {
    new Percentual(-5);
})->throws(InvalidArgumentException::class, 'maior que 0');

test('rejeita percentual acima de 100', function () {
    new Percentual(100.01);
})->throws(InvalidArgumentException::class, 'maior que 100');

test('compara dois percentuais iguais', function () {
    $a = new Percentual(25.0);
    $b = new Percentual(25.0);
    expect($a->equals($b))->toBeTrue();
});

test('compara dois percentuais diferentes', function () {
    $a = new Percentual(25.0);
    $b = new Percentual(30.0);
    expect($a->equals($b))->toBeFalse();
});
