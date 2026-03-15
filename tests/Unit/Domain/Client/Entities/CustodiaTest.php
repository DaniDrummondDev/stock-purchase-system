<?php

use App\Domain\Client\Entities\Custodia;
use App\Domain\Client\ValueObjects\Money;

test('cria custodia vazia', function () {
    $custodia = new Custodia('id', 'cliente-id', 'PETR4');

    expect($custodia->ticker())->toBe('PETR4')
        ->and($custodia->quantidade())->toBe(0)
        ->and($custodia->precoMedio()->isZero())->toBeTrue();
});

test('adiciona compra e calcula preco medio', function () {
    $custodia = new Custodia('id', 'cliente-id', 'PETR4');

    // Primeira compra: 10 ações a R$ 30,00
    $custodia->adicionarCompra(10, Money::fromDecimal(30.00));
    expect($custodia->quantidade())->toBe(10)
        ->and($custodia->precoMedio()->toDecimalString())->toBe('30.00');

    // Segunda compra: 10 ações a R$ 40,00
    // PM = (10*30 + 10*40) / 20 = 700/20 = 35,00
    $custodia->adicionarCompra(10, Money::fromDecimal(40.00));
    expect($custodia->quantidade())->toBe(20)
        ->and($custodia->precoMedio()->toDecimalString())->toBe('35.00');
});

test('venda nao altera preco medio (RN-043)', function () {
    $custodia = new Custodia('id', 'cliente-id', 'VALE3', 100, Money::fromDecimal(85.00));

    $custodia->removerVenda(30);

    expect($custodia->quantidade())->toBe(70)
        ->and($custodia->precoMedio()->toDecimalString())->toBe('85.00');
});

test('rejeita venda com quantidade insuficiente', function () {
    $custodia = new Custodia('id', 'cliente-id', 'PETR4', 10, Money::fromDecimal(30.00));
    $custodia->removerVenda(15);
})->throws(InvalidArgumentException::class, 'Quantidade insuficiente');

test('calcula valor atual', function () {
    $custodia = new Custodia('id', 'cliente-id', 'PETR4', 10, Money::fromDecimal(30.00));
    $valorAtual = $custodia->valorAtual(Money::fromDecimal(35.00));

    expect($valorAtual->toDecimalString())->toBe('350.00');
});

test('calcula lucro ou prejuizo', function () {
    $custodia = new Custodia('id', 'cliente-id', 'PETR4', 10, Money::fromDecimal(30.00));

    // Lucro: cotação atual R$ 35, PM R$ 30, 10 ações = R$ 50
    $lucro = $custodia->lucroOuPrejuizo(Money::fromDecimal(35.00));
    expect($lucro->toDecimalString())->toBe('50.00');
});

test('normaliza ticker para uppercase', function () {
    $custodia = new Custodia('id', 'cliente-id', 'petr4');
    expect($custodia->ticker())->toBe('PETR4');
});
