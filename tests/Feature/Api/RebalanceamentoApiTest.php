<?php

use App\Infrastructure\Persistence\Models\Cesta;
use App\Infrastructure\Persistence\Models\CestaAtivo;
use App\Infrastructure\Persistence\Models\Cliente;
use App\Infrastructure\Persistence\Models\Cotacao;
use App\Infrastructure\Persistence\Models\Custodia;

beforeEach(fn () => authenticateAsAdmin());

function setupRebalanceamentoScenario(): void
{
    // Create active cesta
    $cesta = Cesta::create(['nome' => 'Top Five', 'ativo' => true]);

    foreach ([['PETR4', 30], ['VALE3', 25], ['ITUB4', 20], ['BBDC4', 15], ['WEGE3', 10]] as $a) {
        CestaAtivo::create(['cesta_id' => $cesta->id, 'ticker' => $a[0], 'percentual' => $a[1]]);
    }

    // Create client with imbalanced portfolio
    $cliente = Cliente::create([
        'nome' => 'Cliente A',
        'cpf' => '12345678909',
        'email' => 'a@test.com',
        'valor_mensal' => 3000,
        'status' => 'ativo',
        'valor_total_investido' => 1000,
    ]);

    // Portfolio heavily weighted in PETR4 (should trigger rebalancing)
    Custodia::create([
        'cliente_id' => $cliente->id,
        'ticker' => 'PETR4',
        'quantidade' => 100,
        'preco_medio' => 35.00,
    ]);

    Custodia::create([
        'cliente_id' => $cliente->id,
        'ticker' => 'VALE3',
        'quantidade' => 5,
        'preco_medio' => 60.00,
    ]);

    // Cotações
    foreach ([['PETR4', 35], ['VALE3', 62], ['ITUB4', 30], ['BBDC4', 15], ['WEGE3', 40]] as [$ticker, $preco]) {
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

test('POST executar rebalanceamento tipo B com sucesso', function () {
    setupRebalanceamentoScenario();

    $response = $this->postJson('/api/admin/rebalanceamento/executar');

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'data' => ['tipo', 'totalClientesAnalisados', 'totalClientesRebalanceados', 'resultados'],
        ]);
});

test('rebalanceamento rejeita sem cesta ativa', function () {
    Cliente::create([
        'nome' => 'Test',
        'cpf' => '12345678909',
        'email' => 'test@test.com',
        'valor_mensal' => 3000,
        'status' => 'ativo',
        'valor_total_investido' => 0,
    ]);

    $response = $this->postJson('/api/admin/rebalanceamento/executar');

    $response->assertStatus(422)
        ->assertJson(['error' => ['code' => 'CESTA_NAO_ENCONTRADA']]);
});

test('rebalanceamento para cliente especifico', function () {
    setupRebalanceamentoScenario();
    $cliente = Cliente::first();

    $response = $this->postJson('/api/admin/rebalanceamento/executar', [
        'clienteId' => $cliente->id,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($response->json('data.totalClientesAnalisados'))->toBe(1);
});
