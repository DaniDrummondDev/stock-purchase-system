<?php

use App\Infrastructure\Persistence\Models\Cotacao;

test('POST importar processa arquivo sync', function () {
    $response = $this->postJson('/api/admin/cotacoes/importar', [
        'filePath' => 'tests/fixtures/cotahist_sample.txt',
        'async' => false,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Importação concluída',
        ])
        ->assertJsonStructure(['data' => ['totalLines', 'totalImported', 'totalSkipped']]);

    expect($response->json('data.totalImported'))->toBeGreaterThan(0);
    $this->assertDatabaseHas('cotacoes', ['ticker' => 'PETR4']);
});

test('POST importar rejeita arquivo inexistente', function () {
    $response = $this->postJson('/api/admin/cotacoes/importar', [
        'filePath' => 'cotacoes/NAO_EXISTE.TXT',
        'async' => false,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'ARQUIVO_NAO_ENCONTRADO'],
        ]);
});

test('GET /cotacoes/{ticker} retorna cotacao mais recente', function () {
    Cotacao::create([
        'ticker' => 'PETR4',
        'data_pregao' => '2026-02-24',
        'preco_fechamento' => 34.50,
        'preco_abertura' => 34.00,
        'preco_maximo' => 35.00,
        'preco_minimo' => 33.80,
        'tipo_mercado' => 'padrao',
        'cod_bdi' => '02',
        'volume' => 100000.00,
    ]);

    Cotacao::create([
        'ticker' => 'PETR4',
        'data_pregao' => '2026-02-25',
        'preco_fechamento' => 35.80,
        'preco_abertura' => 35.20,
        'preco_maximo' => 36.50,
        'preco_minimo' => 34.80,
        'tipo_mercado' => 'padrao',
        'cod_bdi' => '02',
        'volume' => 107400.00,
    ]);

    $response = $this->getJson('/api/cotacoes/PETR4');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'ticker' => 'PETR4',
                'dataPregao' => '2026-02-25',
                'precoFechamento' => '35.80',
            ],
        ]);
});

test('GET /cotacoes/{ticker}/{data} retorna cotacao por data', function () {
    Cotacao::create([
        'ticker' => 'VALE3',
        'data_pregao' => '2026-02-25',
        'preco_fechamento' => 63.50,
        'preco_abertura' => 62.50,
        'preco_maximo' => 63.80,
        'preco_minimo' => 61.80,
        'tipo_mercado' => 'padrao',
        'cod_bdi' => '02',
        'volume' => 158750.00,
    ]);

    $response = $this->getJson('/api/cotacoes/VALE3/2026-02-25');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'ticker' => 'VALE3',
                'dataPregao' => '2026-02-25',
                'precoFechamento' => '63.50',
            ],
        ]);
});

test('GET /cotacoes/{ticker} retorna 404 para ticker desconhecido', function () {
    $response = $this->getJson('/api/cotacoes/XXXX9');

    $response->assertStatus(404)
        ->assertJson(['success' => false]);
});
