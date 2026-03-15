<?php

use App\Domain\Tax\Services\IRVendaService;

test('vendas abaixo de R$20k sao isentas (RN-058)', function () {
    $service = new IRVendaService;

    $result = $service->calcular([
        ['ticker' => 'BBDC4', 'quantidade' => 10, 'precoVenda' => 15.00, 'precoMedio' => 14.00],
        ['ticker' => 'WEGE3', 'quantidade' => 2, 'precoVenda' => 40.00, 'precoMedio' => 38.00],
    ]);

    expect($result['isento'])->toBeTrue();
    expect($result['totalVendas'])->toBe(230.00);
    expect($result['valorIR'])->toBe(0.0);
});

test('vendas acima de R$20k com lucro cobram 20% (RN-059)', function () {
    $service = new IRVendaService;

    $result = $service->calcular([
        ['ticker' => 'BBDC4', 'quantidade' => 500, 'precoVenda' => 16.00, 'precoMedio' => 14.00],
        ['ticker' => 'WEGE3', 'quantidade' => 300, 'precoVenda' => 45.00, 'precoMedio' => 38.00],
    ]);

    // Total: 8000 + 13500 = 21500 > 20000
    expect($result['isento'])->toBeFalse();
    expect($result['totalVendas'])->toBe(21500.00);

    // Lucro: 500*(16-14) + 300*(45-38) = 1000 + 2100 = 3100
    expect($result['lucroLiquido'])->toBe(3100.00);

    // IR: 3100 * 20% = 620
    expect($result['valorIR'])->toBe(620.00);
});

test('vendas acima de R$20k com prejuizo nao cobram IR (RN-061)', function () {
    $service = new IRVendaService;

    $result = $service->calcular([
        ['ticker' => 'PETR4', 'quantidade' => 400, 'precoVenda' => 32.00, 'precoMedio' => 35.00],
        ['ticker' => 'VALE3', 'quantidade' => 200, 'precoVenda' => 58.00, 'precoMedio' => 55.00],
    ]);

    // Total: 12800 + 11600 = 24400 > 20000
    expect($result['isento'])->toBeFalse();

    // Lucro: 400*(32-35) + 200*(58-55) = -1200 + 600 = -600
    expect($result['lucroLiquido'])->toBe(-600.00);
    expect($result['valorIR'])->toBe(0.0);
});

test('retorna detalhes por ticker', function () {
    $service = new IRVendaService;

    $result = $service->calcular([
        ['ticker' => 'PETR4', 'quantidade' => 10, 'precoVenda' => 40.00, 'precoMedio' => 35.00],
    ]);

    expect($result['detalhes'])->toHaveCount(1);
    expect($result['detalhes'][0]['ticker'])->toBe('PETR4');
    expect($result['detalhes'][0]['lucro'])->toBe(50.00);
});

test('vendas exatamente R$20k sao isentas', function () {
    $service = new IRVendaService;

    $result = $service->calcular([
        ['ticker' => 'PETR4', 'quantidade' => 500, 'precoVenda' => 40.00, 'precoMedio' => 35.00],
    ]);

    // Total: 20000 <= 20000
    expect($result['isento'])->toBeTrue();
    expect($result['valorIR'])->toBe(0.0);
});

test('lista vazia retorna isento', function () {
    $service = new IRVendaService;
    $result = $service->calcular([]);

    expect($result['isento'])->toBeTrue();
    expect($result['valorIR'])->toBe(0.0);
});
