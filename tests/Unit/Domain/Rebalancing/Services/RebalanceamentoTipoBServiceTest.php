<?php

use App\Domain\Rebalancing\Services\RebalanceamentoTipoBService;

test('detecta desvio acima do limiar (RN-050/051)', function () {
    $service = new RebalanceamentoTipoBService;

    // Portfolio: PETR4 worth 400 (40%), VALE3 worth 600 (60%) — total 1000
    // Alvo: PETR4 30%, VALE3 25%, ITUB4 20%, BBDC4 15%, WEGE3 10%
    // Desvio PETR4: 40-30 = +10pp > 5pp
    // Desvio VALE3: 60-25 = +35pp > 5pp
    $custodias = [
        'PETR4' => ['quantidade' => 10, 'precoMedio' => 35.00],
        'VALE3' => ['quantidade' => 10, 'precoMedio' => 55.00],
    ];

    $percentuais = ['PETR4' => 30, 'VALE3' => 25, 'ITUB4' => 20, 'BBDC4' => 15, 'WEGE3' => 10];
    $cotacoes = ['PETR4' => 40.00, 'VALE3' => 60.00, 'ITUB4' => 30.00, 'BBDC4' => 15.00, 'WEGE3' => 40.00];

    $result = $service->analisar($custodias, $percentuais, $cotacoes);

    expect($result['necessario'])->toBeTrue();
    expect($result['vendas'])->not->toBeEmpty();
});

test('nao rebalanceia quando desvios dentro do limiar', function () {
    $service = new RebalanceamentoTipoBService;

    // Portfolio perfeitamente balanceado
    $custodias = [
        'PETR4' => ['quantidade' => 30, 'precoMedio' => 10.00],
        'VALE3' => ['quantidade' => 25, 'precoMedio' => 10.00],
        'ITUB4' => ['quantidade' => 20, 'precoMedio' => 10.00],
        'BBDC4' => ['quantidade' => 15, 'precoMedio' => 10.00],
        'WEGE3' => ['quantidade' => 10, 'precoMedio' => 10.00],
    ];

    $percentuais = ['PETR4' => 30, 'VALE3' => 25, 'ITUB4' => 20, 'BBDC4' => 15, 'WEGE3' => 10];
    $cotacoes = ['PETR4' => 10.00, 'VALE3' => 10.00, 'ITUB4' => 10.00, 'BBDC4' => 10.00, 'WEGE3' => 10.00];

    $result = $service->analisar($custodias, $percentuais, $cotacoes);

    expect($result['necessario'])->toBeFalse();
    expect($result['vendas'])->toBeEmpty();
    expect($result['compras'])->toBeEmpty();
});

test('carteira vazia retorna nao necessario', function () {
    $service = new RebalanceamentoTipoBService;

    $result = $service->analisar([], ['PETR4' => 30], ['PETR4' => 35.00]);

    expect($result['necessario'])->toBeFalse();
});

test('retorna desvios detalhados por ticker', function () {
    $service = new RebalanceamentoTipoBService;

    $custodias = [
        'PETR4' => ['quantidade' => 100, 'precoMedio' => 35.00],
    ];

    $percentuais = ['PETR4' => 30, 'VALE3' => 70];
    $cotacoes = ['PETR4' => 35.00, 'VALE3' => 62.00];

    $result = $service->analisar($custodias, $percentuais, $cotacoes);

    expect($result['desvios'])->toHaveKey('PETR4');
    expect($result['desvios']['PETR4']['real'])->toBe(100.0);
    expect($result['desvios']['PETR4']['alvo'])->toBe(30);
    expect($result['desvios']['PETR4']['desvio'])->toBe(70.0);
});

test('limiar customizado funciona', function () {
    $service = new RebalanceamentoTipoBService;

    $custodias = [
        'PETR4' => ['quantidade' => 33, 'precoMedio' => 10.00],
        'VALE3' => ['quantidade' => 67, 'precoMedio' => 10.00],
    ];

    $percentuais = ['PETR4' => 30, 'VALE3' => 70];
    $cotacoes = ['PETR4' => 10.00, 'VALE3' => 10.00];

    // Desvio PETR4: 33-30 = 3pp — below 5pp but above 2pp
    $result5 = $service->analisar($custodias, $percentuais, $cotacoes, 5.0);
    $result2 = $service->analisar($custodias, $percentuais, $cotacoes, 2.0);

    expect($result5['necessario'])->toBeFalse();
    expect($result2['necessario'])->toBeTrue();
});
