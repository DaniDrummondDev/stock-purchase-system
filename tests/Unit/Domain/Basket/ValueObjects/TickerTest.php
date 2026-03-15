<?php

use App\Domain\Basket\ValueObjects\Ticker;

test('cria ticker valido', function () {
    $t = new Ticker('PETR4');
    expect($t->value())->toBe('PETR4');
});

test('normaliza ticker para uppercase', function () {
    $t = new Ticker('petr4');
    expect($t->value())->toBe('PETR4');
});

test('faz trim no ticker', function () {
    $t = new Ticker('  VALE3  ');
    expect($t->value())->toBe('VALE3');
});

test('rejeita ticker vazio', function () {
    new Ticker('');
})->throws(InvalidArgumentException::class, 'vazio');

test('rejeita ticker com apenas espacos', function () {
    new Ticker('   ');
})->throws(InvalidArgumentException::class, 'vazio');

test('rejeita ticker com mais de 12 caracteres', function () {
    new Ticker('ABCDEFGHIJKLM');
})->throws(InvalidArgumentException::class, '12 caracteres');

test('compara dois tickers iguais', function () {
    $a = new Ticker('PETR4');
    $b = new Ticker('petr4');
    expect($a->equals($b))->toBeTrue();
});

test('converte ticker para string', function () {
    $t = new Ticker('ITUB4');
    expect((string) $t)->toBe('ITUB4');
});
