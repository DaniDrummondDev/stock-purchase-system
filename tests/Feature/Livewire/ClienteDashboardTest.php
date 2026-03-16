<?php

use App\Infrastructure\Persistence\Models\Cliente;
use App\Infrastructure\Persistence\Models\Cotacao;
use App\Infrastructure\Persistence\Models\Custodia;
use App\Presentation\Livewire\Dashboard\ClienteDashboard;
use Livewire\Livewire;

beforeEach(fn () => authenticateAsAdmin());

test('dashboard renders with client data', function () {
    $cliente = Cliente::create([
        'nome' => 'João Silva',
        'cpf' => '12345678909',
        'email' => 'joao@test.com',
        'valor_mensal' => 3000,
        'status' => 'ativo',
        'valor_total_investido' => 1000,
    ]);

    Custodia::create([
        'cliente_id' => $cliente->id,
        'ticker' => 'PETR4',
        'quantidade' => 10,
        'preco_medio' => 35.00,
    ]);

    Cotacao::create([
        'ticker' => 'PETR4',
        'data_pregao' => '2026-02-25',
        'preco_fechamento' => 37.00,
        'preco_abertura' => 35.00,
        'preco_maximo' => 38.00,
        'preco_minimo' => 34.00,
        'tipo_mercado' => 'padrao',
        'cod_bdi' => '02',
        'volume' => 10000,
    ]);

    Livewire::test(ClienteDashboard::class)
        ->assertSee('João Silva')
        ->assertSee('PETR4');
});

test('dashboard shows empty state without custodias', function () {
    Cliente::create([
        'nome' => 'Maria Santos',
        'cpf' => '98765432100',
        'email' => 'maria@test.com',
        'valor_mensal' => 3000,
        'status' => 'ativo',
        'valor_total_investido' => 0,
    ]);

    Livewire::test(ClienteDashboard::class)
        ->assertSee('Nenhum ativo na carteira');
});
