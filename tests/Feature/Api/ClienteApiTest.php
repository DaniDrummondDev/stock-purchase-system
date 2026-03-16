<?php

use App\Infrastructure\Persistence\Models\Cliente;

beforeEach(fn () => authenticateAsAdmin());

test('adesao cria cliente com sucesso', function () {
    $response = $this->postJson('/api/clientes/adesao', [
        'nome' => 'João Silva',
        'cpf' => '12345678909',
        'email' => 'joao@email.com',
        'valorMensal' => 1000.00,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Cliente aderiu com sucesso',
        ])
        ->assertJsonStructure([
            'data' => ['clienteId', 'contaGraficaNumero'],
        ]);

    $this->assertDatabaseHas('clientes', [
        'cpf' => '12345678909',
        'status' => 'ativo',
    ]);

    $this->assertDatabaseHas('contas_graficas', [
        'cliente_id' => $response->json('data.clienteId'),
    ]);
});

test('adesao rejeita cpf duplicado', function () {
    Cliente::create([
        'nome' => 'Existente',
        'cpf' => '12345678909',
        'email' => 'existente@email.com',
        'valor_mensal' => 500,
        'status' => 'ativo',
        'valor_total_investido' => 0,
    ]);

    $response = $this->postJson('/api/clientes/adesao', [
        'nome' => 'João Silva',
        'cpf' => '12345678909',
        'email' => 'joao@email.com',
        'valorMensal' => 1000.00,
    ]);

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'CLIENTE_CPF_DUPLICADO'],
        ]);
});

test('adesao rejeita valor mensal abaixo do minimo', function () {
    $response = $this->postJson('/api/clientes/adesao', [
        'nome' => 'João Silva',
        'cpf' => '12345678909',
        'email' => 'joao@email.com',
        'valorMensal' => 50.00,
    ]);

    $response->assertStatus(422);
});

test('adesao rejeita dados incompletos', function () {
    $response = $this->postJson('/api/clientes/adesao', [
        'nome' => 'João Silva',
    ]);

    $response->assertStatus(422);
});

test('saida muda status para inativo', function () {
    $cliente = Cliente::create([
        'nome' => 'João Silva',
        'cpf' => '12345678909',
        'email' => 'joao@email.com',
        'valor_mensal' => 1000,
        'status' => 'ativo',
        'valor_total_investido' => 0,
    ]);

    $response = $this->postJson("/api/clientes/{$cliente->id}/saida");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'status' => 'inativo',
    ]);
});

test('saida rejeita cliente ja inativo', function () {
    $cliente = Cliente::create([
        'nome' => 'João Silva',
        'cpf' => '12345678909',
        'email' => 'joao@email.com',
        'valor_mensal' => 1000,
        'status' => 'inativo',
        'valor_total_investido' => 0,
    ]);

    $response = $this->postJson("/api/clientes/{$cliente->id}/saida");

    $response->assertStatus(422);
});

test('saida retorna 404 para cliente inexistente', function () {
    $response = $this->postJson('/api/clientes/uuid-invalido/saida');

    $response->assertStatus(404);
});

test('alterar valor mensal com sucesso', function () {
    $cliente = Cliente::create([
        'nome' => 'João Silva',
        'cpf' => '12345678909',
        'email' => 'joao@email.com',
        'valor_mensal' => 1000,
        'status' => 'ativo',
        'valor_total_investido' => 0,
    ]);

    $response = $this->putJson("/api/clientes/{$cliente->id}/valor-mensal", [
        'valorMensal' => 1500.00,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('clientes', [
        'id' => $cliente->id,
        'valor_mensal' => 1500.00,
    ]);
});

test('consultar carteira retorna dados do cliente', function () {
    $cliente = Cliente::create([
        'nome' => 'João Silva',
        'cpf' => '12345678909',
        'email' => 'joao@email.com',
        'valor_mensal' => 1000,
        'status' => 'ativo',
        'valor_total_investido' => 5000,
    ]);

    $response = $this->getJson("/api/clientes/{$cliente->id}/carteira");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'nome' => 'João Silva',
                'status' => 'ativo',
                'valorMensal' => '1000.00',
                'valorTotalInvestido' => '5000.00',
                'ativos' => [],
            ],
        ]);
});
