<?php

use App\Infrastructure\Persistence\Models\Cesta;
use App\Infrastructure\Persistence\Models\CestaAtivo;

beforeEach(fn () => authenticateAsAdmin());

function validCestaPayload(string $nome = 'Top Five - Março 2026'): array
{
    return [
        'nome' => $nome,
        'ativos' => [
            ['ticker' => 'PETR4', 'percentual' => 30],
            ['ticker' => 'VALE3', 'percentual' => 25],
            ['ticker' => 'ITUB4', 'percentual' => 20],
            ['ticker' => 'BBDC4', 'percentual' => 15],
            ['ticker' => 'WEGE3', 'percentual' => 10],
        ],
    ];
}

function createCestaInDb(string $nome = 'Top Five Antiga', bool $ativo = true): Cesta
{
    $cesta = Cesta::create([
        'nome' => $nome,
        'ativo' => $ativo,
        'data_desativacao' => $ativo ? null : now(),
    ]);

    $ativos = [
        ['ticker' => 'PETR4', 'percentual' => 30],
        ['ticker' => 'VALE3', 'percentual' => 25],
        ['ticker' => 'ITUB4', 'percentual' => 20],
        ['ticker' => 'BBDC4', 'percentual' => 15],
        ['ticker' => 'WEGE3', 'percentual' => 10],
    ];

    foreach ($ativos as $ativo) {
        CestaAtivo::create([
            'cesta_id' => $cesta->id,
            ...$ativo,
        ]);
    }

    return $cesta;
}

test('POST cria primeira cesta com sucesso', function () {
    $response = $this->postJson('/api/admin/cesta', validCestaPayload());

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Cesta criada com sucesso',
        ])
        ->assertJsonStructure(['data' => ['cestaId']]);

    $this->assertDatabaseHas('cestas', [
        'nome' => 'Top Five - Março 2026',
        'ativo' => true,
    ]);

    $this->assertDatabaseCount('cesta_ativos', 5);
});

test('POST substitui cesta existente (desativa antiga, cria nova)', function () {
    $antigaCesta = createCestaInDb();

    $novaPayload = [
        'nome' => 'Top Five - Abril 2026',
        'ativos' => [
            ['ticker' => 'PETR4', 'percentual' => 25],
            ['ticker' => 'VALE3', 'percentual' => 20],
            ['ticker' => 'ITUB4', 'percentual' => 20],
            ['ticker' => 'ABEV3', 'percentual' => 20],
            ['ticker' => 'RENT3', 'percentual' => 15],
        ],
    ];

    $response = $this->postJson('/api/admin/cesta', $novaPayload);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Cesta atualizada com sucesso',
        ]);

    $this->assertDatabaseHas('cestas', [
        'id' => $antigaCesta->id,
        'ativo' => false,
    ]);

    $this->assertDatabaseHas('cestas', [
        'nome' => 'Top Five - Abril 2026',
        'ativo' => true,
    ]);
});

test('POST rejeita ativos diferente de 5', function () {
    $payload = [
        'nome' => 'Inválida',
        'ativos' => [
            ['ticker' => 'PETR4', 'percentual' => 50],
            ['ticker' => 'VALE3', 'percentual' => 50],
        ],
    ];

    $response = $this->postJson('/api/admin/cesta', $payload);
    $response->assertStatus(422);
});

test('POST rejeita soma diferente de 100%', function () {
    $payload = [
        'nome' => 'Inválida',
        'ativos' => [
            ['ticker' => 'PETR4', 'percentual' => 30],
            ['ticker' => 'VALE3', 'percentual' => 25],
            ['ticker' => 'ITUB4', 'percentual' => 20],
            ['ticker' => 'BBDC4', 'percentual' => 15],
            ['ticker' => 'WEGE3', 'percentual' => 5],
        ],
    ];

    $response = $this->postJson('/api/admin/cesta', $payload);
    $response->assertStatus(422);
});

test('POST rejeita percentual zero', function () {
    $payload = [
        'nome' => 'Inválida',
        'ativos' => [
            ['ticker' => 'PETR4', 'percentual' => 40],
            ['ticker' => 'VALE3', 'percentual' => 25],
            ['ticker' => 'ITUB4', 'percentual' => 20],
            ['ticker' => 'BBDC4', 'percentual' => 15],
            ['ticker' => 'WEGE3', 'percentual' => 0],
        ],
    ];

    $response = $this->postJson('/api/admin/cesta', $payload);
    $response->assertStatus(422);
});

test('POST rejeita tickers duplicados', function () {
    $payload = [
        'nome' => 'Inválida',
        'ativos' => [
            ['ticker' => 'PETR4', 'percentual' => 30],
            ['ticker' => 'PETR4', 'percentual' => 25],
            ['ticker' => 'ITUB4', 'percentual' => 20],
            ['ticker' => 'BBDC4', 'percentual' => 15],
            ['ticker' => 'WEGE3', 'percentual' => 10],
        ],
    ];

    $response = $this->postJson('/api/admin/cesta', $payload);
    $response->assertStatus(422);
});

test('GET /atual retorna cesta ativa', function () {
    createCestaInDb('Top Five Ativa');

    $response = $this->getJson('/api/admin/cesta/atual');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'nome' => 'Top Five Ativa',
                'ativo' => true,
            ],
        ])
        ->assertJsonCount(5, 'data.ativos');
});

test('GET /atual retorna 404 quando nenhuma cesta ativa', function () {
    $response = $this->getJson('/api/admin/cesta/atual');

    $response->assertStatus(404)
        ->assertJson(['success' => false]);
});

test('GET /historico retorna todas as cestas ordenadas', function () {
    createCestaInDb('Cesta Antiga', false);
    createCestaInDb('Cesta Nova', true);

    $response = $this->getJson('/api/admin/cesta/historico');

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonCount(2, 'data');
});
