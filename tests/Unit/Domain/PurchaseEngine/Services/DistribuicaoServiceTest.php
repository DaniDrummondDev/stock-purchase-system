<?php

use App\Domain\PurchaseEngine\Services\DistribuicaoService;

test('distribui proporcionalmente ao aporte', function () {
    $service = new DistribuicaoService;

    $aportes = [
        'clienteA' => 100000,  // 28.57%
        'clienteB' => 200000,  // 57.14%
        'clienteC' => 50000,   // 14.29%
    ];

    $quantidades = ['PETR4' => 30];
    $precos = ['PETR4' => 35.00];

    $result = $service->distribuir($aportes, $quantidades, $precos);

    // clienteA: TRUNCAR(30 * 0.2857) = 8
    // clienteB: TRUNCAR(30 * 0.5714) = 17
    // clienteC: TRUNCAR(30 * 0.1429) = 4
    // Total: 29, residuo: 1
    $totalDistribuido = array_sum(array_map(fn ($a) => $a['quantidade'], $result->alocacoes));
    expect($totalDistribuido)->toBe(29);
    expect($result->residuos['PETR4'])->toBe(1);
});

test('trunca e nunca arredonda para cima', function () {
    $service = new DistribuicaoService;

    $aportes = [
        'clienteA' => 100000,
        'clienteB' => 100000,
        'clienteC' => 100000,
    ];

    // 10 / 3 = 3.33 each → TRUNCAR = 3 each → 9 total → 1 resíduo
    $quantidades = ['PETR4' => 10];
    $precos = ['PETR4' => 35.00];

    $result = $service->distribuir($aportes, $quantidades, $precos);

    foreach ($result->alocacoes as $alocacao) {
        expect($alocacao['quantidade'])->toBe(3);
    }

    expect($result->residuos['PETR4'])->toBe(1);
});

test('calcula residuos corretamente', function () {
    $service = new DistribuicaoService;

    $aportes = [
        'clienteA' => 100000,
        'clienteB' => 200000,
        'clienteC' => 50000,
    ];

    $quantidades = [
        'PETR4' => 30,
        'VALE3' => 14,
        'WEGE3' => 8,
    ];

    $precos = [
        'PETR4' => 35.00,
        'VALE3' => 62.00,
        'WEGE3' => 40.00,
    ];

    $result = $service->distribuir($aportes, $quantidades, $precos);

    // Verify no residue is negative
    foreach ($result->residuos as $ticker => $qty) {
        expect($qty)->toBeGreaterThan(0);
    }
});

test('distribuicao vazia quando sem aportes', function () {
    $service = new DistribuicaoService;

    $result = $service->distribuir([], ['PETR4' => 10], ['PETR4' => 35.00]);

    expect($result->alocacoes)->toBeEmpty();
    expect($result->residuos)->toHaveKey('PETR4');
});

test('distribui multiplos tickers', function () {
    $service = new DistribuicaoService;

    $aportes = ['clienteA' => 100000, 'clienteB' => 100000];
    $quantidades = ['PETR4' => 20, 'VALE3' => 10];
    $precos = ['PETR4' => 35.00, 'VALE3' => 62.00];

    $result = $service->distribuir($aportes, $quantidades, $precos);

    // Each client gets 50%
    // PETR4: 20 * 50% = 10 each
    // VALE3: 10 * 50% = 5 each
    $petr4Alocacoes = array_filter($result->alocacoes, fn ($a) => $a['ticker'] === 'PETR4');
    $vale3Alocacoes = array_filter($result->alocacoes, fn ($a) => $a['ticker'] === 'VALE3');

    foreach ($petr4Alocacoes as $a) {
        expect($a['quantidade'])->toBe(10);
    }

    foreach ($vale3Alocacoes as $a) {
        expect($a['quantidade'])->toBe(5);
    }
});
