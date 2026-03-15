<?php

use App\Domain\Basket\Entities\Cesta;
use App\Domain\Basket\Entities\CestaAtivo;
use App\Domain\Basket\ValueObjects\Percentual;
use App\Domain\Basket\ValueObjects\Ticker;
use App\Domain\Client\Entities\Cliente;
use App\Domain\Client\ValueObjects\Cpf;
use App\Domain\Client\ValueObjects\Email;
use App\Domain\Client\ValueObjects\Money;
use App\Domain\MarketData\Entities\Cotacao;
use App\Domain\PurchaseEngine\Services\ConsolidacaoService;
use Illuminate\Support\Str;

function makeCesta(): Cesta
{
    return new Cesta(
        id: (string) Str::uuid(),
        nome: 'Test Cesta',
        ativos: [
            new CestaAtivo((string) Str::uuid(), new Ticker('PETR4'), new Percentual(30)),
            new CestaAtivo((string) Str::uuid(), new Ticker('VALE3'), new Percentual(25)),
            new CestaAtivo((string) Str::uuid(), new Ticker('ITUB4'), new Percentual(20)),
            new CestaAtivo((string) Str::uuid(), new Ticker('BBDC4'), new Percentual(15)),
            new CestaAtivo((string) Str::uuid(), new Ticker('WEGE3'), new Percentual(10)),
        ],
    );
}

function makeCliente(string $nome, float $valorMensal): Cliente
{
    return new Cliente(
        id: (string) Str::uuid(),
        nome: $nome,
        cpf: new Cpf('12345678909'),
        email: new Email($nome.'@test.com'),
        valorMensal: Money::fromDecimal($valorMensal),
    );
}

function makeCotacao(string $ticker, float $preco): Cotacao
{
    return new Cotacao(
        ticker: $ticker,
        dataPregao: new DateTimeImmutable('2026-02-25'),
        precoFechamento: $preco,
        precoAbertura: $preco,
        precoMaximo: $preco,
        precoMinimo: $preco,
        tipoMercado: 'padrao',
        codBdi: '02',
    );
}

test('consolida aportes de 3 clientes', function () {
    $service = new ConsolidacaoService;
    $clientes = [
        makeCliente('A', 3000), // aporte/3 = 1000
        makeCliente('B', 6000), // aporte/3 = 2000
        makeCliente('C', 1500), // aporte/3 = 500
    ];

    $cotacoes = [
        'PETR4' => makeCotacao('PETR4', 35.00),
        'VALE3' => makeCotacao('VALE3', 62.00),
        'ITUB4' => makeCotacao('ITUB4', 30.00),
        'BBDC4' => makeCotacao('BBDC4', 15.00),
        'WEGE3' => makeCotacao('WEGE3', 40.00),
    ];

    $result = $service->consolidar($clientes, makeCesta(), $cotacoes, []);

    // Total: 100000 + 200000 + 50000 = 350000 centavos = R$3500
    expect($result->valorTotal)->toBe(350000);
    expect($result->aportesPorCliente)->toHaveCount(3);
});

test('calcula quantidades por ticker com TRUNCAR', function () {
    $service = new ConsolidacaoService;
    $clientes = [makeCliente('A', 3000), makeCliente('B', 6000), makeCliente('C', 1500)];

    $cotacoes = [
        'PETR4' => makeCotacao('PETR4', 35.00),
        'VALE3' => makeCotacao('VALE3', 62.00),
        'ITUB4' => makeCotacao('ITUB4', 30.00),
        'BBDC4' => makeCotacao('BBDC4', 15.00),
        'WEGE3' => makeCotacao('WEGE3', 40.00),
    ];

    $result = $service->consolidar($clientes, makeCesta(), $cotacoes, []);

    // PETR4: 3500 * 30% = 1050, 1050/35 = 30
    expect($result->quantidadesDisponiveis['PETR4'])->toBe(30);
    // VALE3: 3500 * 25% = 875, 875/62 = 14 (truncado)
    expect($result->quantidadesDisponiveis['VALE3'])->toBe(14);
});

test('desconta saldo master das quantidades', function () {
    $service = new ConsolidacaoService;
    $clientes = [makeCliente('A', 3000), makeCliente('B', 6000), makeCliente('C', 1500)];

    $cotacoes = [
        'PETR4' => makeCotacao('PETR4', 35.00),
        'VALE3' => makeCotacao('VALE3', 62.00),
        'ITUB4' => makeCotacao('ITUB4', 30.00),
        'BBDC4' => makeCotacao('BBDC4', 15.00),
        'WEGE3' => makeCotacao('WEGE3', 40.00),
    ];

    $saldosMaster = ['PETR4' => 2, 'ITUB4' => 1];

    $result = $service->consolidar($clientes, makeCesta(), $cotacoes, $saldosMaster);

    // PETR4: 30 disponíveis (28 compradas + 2 master)
    expect($result->quantidadesDisponiveis['PETR4'])->toBe(30);

    // Check that orders reflect reduced purchase qty
    $ordenspetr4 = array_filter($result->ordens, fn ($o) => $o->ticker === 'PETR4');
    $totalOrdensPetr4 = array_sum(array_map(fn ($o) => $o->quantidade, $ordenspetr4));
    expect($totalOrdensPetr4)->toBe(28); // 30 - 2 master
});

test('separa lote padrao e fracionario', function () {
    $service = new ConsolidacaoService;
    // Use high-value client to get >=100 shares
    $clientes = [makeCliente('A', 60000)]; // aporte/3 = 20000

    $cotacoes = [
        'PETR4' => makeCotacao('PETR4', 35.00),
        'VALE3' => makeCotacao('VALE3', 10.00),
        'ITUB4' => makeCotacao('ITUB4', 30.00),
        'BBDC4' => makeCotacao('BBDC4', 15.00),
        'WEGE3' => makeCotacao('WEGE3', 40.00),
    ];

    $result = $service->consolidar($clientes, makeCesta(), $cotacoes, []);

    // VALE3: 20000 * 25% = 5000, 5000/10 = 500 => 500 padrao, 0 frac
    $ordensVale = array_filter($result->ordens, fn ($o) => $o->ticker === 'VALE3');
    $padrao = array_filter($ordensVale, fn ($o) => $o->tipoLote === 'padrao');
    $frac = array_filter($ordensVale, fn ($o) => $o->tipoLote === 'fracionario');

    expect(array_sum(array_map(fn ($o) => $o->quantidade, $padrao)))->toBe(500);
    expect($frac)->toBeEmpty();
});
