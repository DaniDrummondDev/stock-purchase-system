<?php

use App\Domain\Rebalancing\Services\RebalanceamentoTipoAService;

test('vende toda posicao de ativos removidos (RN-046/047)', function () {
    $service = new RebalanceamentoTipoAService;

    $custodias = [
        'BBDC4' => ['quantidade' => 10, 'precoMedio' => 14.00],
        'WEGE3' => ['quantidade' => 2, 'precoMedio' => 38.00],
        'PETR4' => ['quantidade' => 8, 'precoMedio' => 35.00],
    ];

    $cotacoes = ['BBDC4' => 15.00, 'WEGE3' => 40.00, 'PETR4' => 35.00, 'ABEV3' => 14.00, 'RENT3' => 48.00];
    $novosPercentuais = ['PETR4' => 25, 'ABEV3' => 20, 'RENT3' => 15, 'VALE3' => 20, 'ITUB4' => 20];

    $result = $service->calcular($custodias, $novosPercentuais, $cotacoes, ['BBDC4', 'WEGE3'], ['ABEV3', 'RENT3']);

    // Should sell all of BBDC4 (10) and WEGE3 (2)
    $vendasRemocao = array_filter($result['vendas'], fn ($v) => $v['tipo'] === 'remocao');
    expect(count($vendasRemocao))->toBe(2);

    $bbdc4 = array_values(array_filter($vendasRemocao, fn ($v) => $v['ticker'] === 'BBDC4'))[0];
    expect($bbdc4['quantidade'])->toBe(10);
    expect($bbdc4['valor'])->toBe(150.00);
});

test('compra novos ativos com valor das vendas (RN-048)', function () {
    $service = new RebalanceamentoTipoAService;

    $custodias = [
        'BBDC4' => ['quantidade' => 10, 'precoMedio' => 14.00],
        'WEGE3' => ['quantidade' => 2, 'precoMedio' => 38.00],
    ];

    $cotacoes = ['BBDC4' => 15.00, 'WEGE3' => 40.00, 'ABEV3' => 14.00, 'RENT3' => 48.00];
    $novosPercentuais = ['ABEV3' => 20, 'RENT3' => 15];

    $result = $service->calcular($custodias, $novosPercentuais, $cotacoes, ['BBDC4', 'WEGE3'], ['ABEV3', 'RENT3']);

    // Vendas: 150 + 80 = 230
    // ABEV3: 57.14% of 230 = 131.43, 131.43/14 = 9
    // RENT3: 42.86% of 230 = 98.57, 98.57/48 = 2
    expect($result['compras'])->not->toBeEmpty();
});

test('sem custodias nao gera operacoes', function () {
    $service = new RebalanceamentoTipoAService;

    $result = $service->calcular([], ['PETR4' => 30], ['PETR4' => 35.00], ['BBDC4'], ['PETR4']);

    expect($result['vendas'])->toBeEmpty();
});
