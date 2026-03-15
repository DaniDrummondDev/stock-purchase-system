<?php

use App\Domain\Basket\Entities\Cesta;
use App\Domain\Basket\Entities\CestaAtivo;
use App\Domain\Basket\ValueObjects\Percentual;
use App\Domain\Basket\ValueObjects\Ticker;
use Illuminate\Support\Str;

function buildAtivos(array $data = []): array
{
    $defaults = [
        ['PETR4', 30], ['VALE3', 25], ['ITUB4', 20], ['BBDC4', 15], ['WEGE3', 10],
    ];

    $items = $data ?: $defaults;

    return array_map(
        fn ($item) => new CestaAtivo(
            id: (string) Str::uuid(),
            ticker: new Ticker($item[0]),
            percentual: new Percentual($item[1]),
        ),
        $items,
    );
}

test('cria cesta valida com 5 ativos somando 100%', function () {
    $cesta = new Cesta(
        id: (string) Str::uuid(),
        nome: 'Top Five - Março 2026',
        ativos: buildAtivos(),
    );

    expect($cesta->nome())->toBe('Top Five - Março 2026');
    expect($cesta->isAtiva())->toBeTrue();
    expect($cesta->ativos())->toHaveCount(5);
    expect($cesta->dataDesativacao())->toBeNull();
    expect($cesta->tickers())->toBe(['PETR4', 'VALE3', 'ITUB4', 'BBDC4', 'WEGE3']);
});

test('rejeita cesta com menos de 5 ativos (RN-014)', function () {
    new Cesta(
        id: (string) Str::uuid(),
        nome: 'Inválida',
        ativos: buildAtivos([['PETR4', 50], ['VALE3', 50]]),
    );
})->throws(InvalidArgumentException::class, 'exatamente 5 ativos');

test('rejeita cesta com mais de 5 ativos (RN-014)', function () {
    new Cesta(
        id: (string) Str::uuid(),
        nome: 'Inválida',
        ativos: buildAtivos([
            ['PETR4', 20], ['VALE3', 20], ['ITUB4', 20],
            ['BBDC4', 15], ['WEGE3', 15], ['ABEV3', 10],
        ]),
    );
})->throws(InvalidArgumentException::class, 'exatamente 5 ativos');

test('rejeita soma diferente de 100% (RN-015)', function () {
    new Cesta(
        id: (string) Str::uuid(),
        nome: 'Inválida',
        ativos: buildAtivos([
            ['PETR4', 30], ['VALE3', 25], ['ITUB4', 20], ['BBDC4', 15], ['WEGE3', 5],
        ]),
    );
})->throws(InvalidArgumentException::class, 'Soma dos percentuais deve ser 100%');

test('rejeita tickers duplicados', function () {
    new Cesta(
        id: (string) Str::uuid(),
        nome: 'Inválida',
        ativos: buildAtivos([
            ['PETR4', 30], ['PETR4', 25], ['ITUB4', 20], ['BBDC4', 15], ['WEGE3', 10],
        ]),
    );
})->throws(InvalidArgumentException::class, 'Ticker duplicado');

test('desativar muda status e define data (RN-017)', function () {
    $cesta = new Cesta(
        id: (string) Str::uuid(),
        nome: 'Top Five',
        ativos: buildAtivos(),
    );

    expect($cesta->isAtiva())->toBeTrue();

    $cesta->desativar();

    expect($cesta->isAtiva())->toBeFalse();
    expect($cesta->dataDesativacao())->toBeInstanceOf(DateTimeImmutable::class);
});

test('rejeita desativar cesta ja desativada', function () {
    $cesta = new Cesta(
        id: (string) Str::uuid(),
        nome: 'Top Five',
        ativos: buildAtivos(),
    );

    $cesta->desativar();
    $cesta->desativar();
})->throws(InvalidArgumentException::class, 'já está desativada');

test('obtem percentual por ticker', function () {
    $cesta = new Cesta(
        id: (string) Str::uuid(),
        nome: 'Top Five',
        ativos: buildAtivos(),
    );

    expect($cesta->percentualPorTicker('PETR4'))->toBe(30.0);
    expect($cesta->percentualPorTicker('WEGE3'))->toBe(10.0);
    expect($cesta->percentualPorTicker('ABEV3'))->toBeNull();
});

test('rejeita nome vazio', function () {
    new Cesta(
        id: (string) Str::uuid(),
        nome: '   ',
        ativos: buildAtivos(),
    );
})->throws(InvalidArgumentException::class, 'Nome da cesta');
