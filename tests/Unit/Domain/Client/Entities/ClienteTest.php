<?php

use App\Domain\Client\Entities\Cliente;
use App\Domain\Client\ValueObjects\Cpf;
use App\Domain\Client\ValueObjects\Email;
use App\Domain\Client\ValueObjects\Money;

function criarCliente(float $valorMensal = 1000.00): Cliente
{
    return new Cliente(
        id: 'test-id',
        nome: 'João Silva',
        cpf: new Cpf('12345678909'),
        email: new Email('joao@email.com'),
        valorMensal: Money::fromDecimal($valorMensal),
    );
}

test('cria cliente com status ativo', function () {
    $cliente = criarCliente();

    expect($cliente->id())->toBe('test-id')
        ->and($cliente->nome())->toBe('João Silva')
        ->and($cliente->cpf()->value())->toBe('12345678909')
        ->and($cliente->email()->value())->toBe('joao@email.com')
        ->and($cliente->status())->toBe('ativo')
        ->and($cliente->isAtivo())->toBeTrue()
        ->and($cliente->valorTotalInvestido()->isZero())->toBeTrue();
});

test('rejeita valor mensal abaixo do minimo', function () {
    criarCliente(99.99);
})->throws(InvalidArgumentException::class, 'Valor mensal mínimo é R$ 100,00');

test('rejeita nome vazio', function () {
    new Cliente(
        id: 'id',
        nome: '   ',
        cpf: new Cpf('12345678909'),
        email: new Email('joao@email.com'),
        valorMensal: Money::fromDecimal(100),
    );
})->throws(InvalidArgumentException::class, 'Nome não pode ser vazio');

test('cliente sai do programa', function () {
    $cliente = criarCliente();
    $cliente->sair();

    expect($cliente->status())->toBe('inativo')
        ->and($cliente->isAtivo())->toBeFalse();
});

test('cliente ja inativo nao pode sair novamente', function () {
    $cliente = criarCliente();
    $cliente->sair();
    $cliente->sair();
})->throws(InvalidArgumentException::class, 'Cliente já está inativo');

test('altera valor mensal', function () {
    $cliente = criarCliente(1000);
    $cliente->alterarValorMensal(Money::fromDecimal(1500));

    expect($cliente->valorMensal()->toDecimalString())->toBe('1500.00');
});

test('rejeita alteracao de valor mensal abaixo do minimo', function () {
    $cliente = criarCliente(1000);
    $cliente->alterarValorMensal(Money::fromDecimal(50));
})->throws(InvalidArgumentException::class);

test('calcula valor de aporte por compra como 1/3 do mensal', function () {
    $cliente = criarCliente(900);
    $aporte = $cliente->valorAportePorCompra();

    expect($aporte->toDecimalString())->toBe('300.00');
});

test('adiciona investimento ao total', function () {
    $cliente = criarCliente();
    $cliente->adicionarInvestimento(Money::fromDecimal(500));
    $cliente->adicionarInvestimento(Money::fromDecimal(300));

    expect($cliente->valorTotalInvestido()->toDecimalString())->toBe('800.00');
});
