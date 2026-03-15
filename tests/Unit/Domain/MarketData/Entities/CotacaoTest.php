<?php

use App\Domain\MarketData\Entities\Cotacao;

test('cria cotacao com dados validos', function () {
    $cotacao = new Cotacao(
        ticker: 'PETR4',
        dataPregao: new DateTimeImmutable('2026-02-25'),
        precoFechamento: 35.80,
        precoAbertura: 35.20,
        precoMaximo: 36.50,
        precoMinimo: 34.80,
        tipoMercado: 'padrao',
        codBdi: '02',
        volume: 107400.00,
    );

    expect($cotacao->ticker())->toBe('PETR4');
    expect($cotacao->precoFechamento())->toBe(35.80);
    expect($cotacao->tipoMercado())->toBe('padrao');
    expect($cotacao->volume())->toBe(107400.00);
});

test('normaliza ticker para uppercase', function () {
    $cotacao = new Cotacao(
        ticker: 'petr4',
        dataPregao: new DateTimeImmutable('2026-02-25'),
        precoFechamento: 35.80,
        precoAbertura: 35.20,
        precoMaximo: 36.50,
        precoMinimo: 34.80,
        tipoMercado: 'padrao',
        codBdi: '02',
    );

    expect($cotacao->ticker())->toBe('PETR4');
});

test('rejeita ticker vazio', function () {
    new Cotacao(
        ticker: '',
        dataPregao: new DateTimeImmutable('2026-02-25'),
        precoFechamento: 35.80,
        precoAbertura: 35.20,
        precoMaximo: 36.50,
        precoMinimo: 34.80,
        tipoMercado: 'padrao',
        codBdi: '02',
    );
})->throws(InvalidArgumentException::class, 'vazio');

test('rejeita tipo de mercado invalido', function () {
    new Cotacao(
        ticker: 'PETR4',
        dataPregao: new DateTimeImmutable('2026-02-25'),
        precoFechamento: 35.80,
        precoAbertura: 35.20,
        precoMaximo: 36.50,
        precoMinimo: 34.80,
        tipoMercado: 'invalido',
        codBdi: '02',
    );
})->throws(InvalidArgumentException::class, 'Tipo de mercado inválido');
