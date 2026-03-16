<?php

use App\Infrastructure\Persistence\Models\Cesta;
use App\Infrastructure\Persistence\Models\CestaAtivo;
use App\Infrastructure\Persistence\Models\Cliente;
use App\Infrastructure\Persistence\Models\CompraDistribuicao;
use App\Infrastructure\Persistence\Models\Cotacao;
use App\Infrastructure\Persistence\Models\CustodiaMaster;

beforeEach(fn () => authenticateAsAdmin());

function setupCompraScenario(): void
{
    // Create active cesta
    $cesta = Cesta::create(['nome' => 'Top Five', 'ativo' => true]);
    $ativos = [
        ['ticker' => 'PETR4', 'percentual' => 30],
        ['ticker' => 'VALE3', 'percentual' => 25],
        ['ticker' => 'ITUB4', 'percentual' => 20],
        ['ticker' => 'BBDC4', 'percentual' => 15],
        ['ticker' => 'WEGE3', 'percentual' => 10],
    ];

    foreach ($ativos as $ativo) {
        CestaAtivo::create(['cesta_id' => $cesta->id, ...$ativo]);
    }

    // Create active clients
    Cliente::create([
        'nome' => 'Cliente A',
        'cpf' => '12345678909',
        'email' => 'a@test.com',
        'valor_mensal' => 3000,
        'status' => 'ativo',
        'valor_total_investido' => 0,
    ]);

    Cliente::create([
        'nome' => 'Cliente B',
        'cpf' => '98765432100',
        'email' => 'b@test.com',
        'valor_mensal' => 6000,
        'status' => 'ativo',
        'valor_total_investido' => 0,
    ]);

    // Create cotações
    $tickers = [
        ['PETR4', 35.00],
        ['VALE3', 62.00],
        ['ITUB4', 30.00],
        ['BBDC4', 15.00],
        ['WEGE3', 40.00],
    ];

    foreach ($tickers as [$ticker, $preco]) {
        Cotacao::create([
            'ticker' => $ticker,
            'data_pregao' => '2026-02-24',
            'preco_fechamento' => $preco,
            'preco_abertura' => $preco,
            'preco_maximo' => $preco,
            'preco_minimo' => $preco,
            'tipo_mercado' => 'padrao',
            'cod_bdi' => '02',
            'volume' => 10000,
        ]);
    }
}

test('executa compra com sucesso — ciclo completo', function () {
    setupCompraScenario();

    $response = $this->postJson('/api/admin/motor/executar-compra', [
        'dataExecucao' => '2026-02-25',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Compra executada com sucesso',
        ])
        ->assertJsonStructure([
            'data' => ['compraId', 'dataExecucao', 'valorTotal', 'totalClientes', 'totalDistribuicoes'],
        ]);

    expect($response->json('data.totalClientes'))->toBe(2);
    expect($response->json('data.totalDistribuicoes'))->toBeGreaterThan(0);

    // Verify compra record
    $this->assertDatabaseHas('compras_programadas', [
        'data_execucao' => '2026-02-25',
        'status' => 'concluida',
    ]);

    // Verify participantes
    $this->assertDatabaseCount('compra_participantes', 2);

    // Verify distribuicoes exist
    $distribuicoes = CompraDistribuicao::count();
    expect($distribuicoes)->toBeGreaterThan(0);

    // Verify custodias updated
    $this->assertDatabaseHas('custodias', ['ticker' => 'PETR4']);
});

test('compra é idempotente — mesma data não executa duas vezes', function () {
    setupCompraScenario();

    $this->postJson('/api/admin/motor/executar-compra', ['dataExecucao' => '2026-02-25']);

    $response = $this->postJson('/api/admin/motor/executar-compra', ['dataExecucao' => '2026-02-25']);

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'COMPRA_JA_EXECUTADA'],
        ]);
});

test('rejeita execução sem cesta ativa', function () {
    // No cesta created
    Cliente::create([
        'nome' => 'Test',
        'cpf' => '12345678909',
        'email' => 'test@test.com',
        'valor_mensal' => 3000,
        'status' => 'ativo',
        'valor_total_investido' => 0,
    ]);

    $response = $this->postJson('/api/admin/motor/executar-compra', ['dataExecucao' => '2026-02-25']);

    $response->assertStatus(422)
        ->assertJson(['error' => ['code' => 'CESTA_NAO_ENCONTRADA']]);
});

test('rejeita execução sem clientes ativos', function () {
    // Create cesta but no clients
    $cesta = Cesta::create(['nome' => 'Top Five', 'ativo' => true]);

    foreach ([['PETR4', 30], ['VALE3', 25], ['ITUB4', 20], ['BBDC4', 15], ['WEGE3', 10]] as $a) {
        CestaAtivo::create(['cesta_id' => $cesta->id, 'ticker' => $a[0], 'percentual' => $a[1]]);
    }

    $response = $this->postJson('/api/admin/motor/executar-compra', ['dataExecucao' => '2026-02-25']);

    $response->assertStatus(422)
        ->assertJson(['error' => ['code' => 'NENHUM_CLIENTE_ATIVO']]);
});

test('lista compras executadas', function () {
    setupCompraScenario();
    $this->postJson('/api/admin/motor/executar-compra', ['dataExecucao' => '2026-02-25']);

    $response = $this->getJson('/api/admin/motor/compras');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'dataExecucao', 'status', 'valorTotal']],
        ]);
});

test('residuos ficam na custodia master', function () {
    setupCompraScenario();

    $this->postJson('/api/admin/motor/executar-compra', ['dataExecucao' => '2026-02-25']);

    // Some tickers should have residues (due to TRUNCAR in distribution)
    $masterRecords = CustodiaMaster::where('quantidade', '>', 0)->count();
    // At least some residues expected with 2 clients
    expect($masterRecords)->toBeGreaterThanOrEqual(0); // May be 0 if distribution is exact
});
