<?php

use App\Infrastructure\Persistence\Models\Cesta;
use App\Infrastructure\Persistence\Models\CestaAtivo;
use App\Presentation\Livewire\Admin\CestaManager;
use App\Presentation\Livewire\Admin\ComprasPanel;
use App\Presentation\Livewire\Admin\ContaMasterPanel;
use Livewire\Livewire;

test('cesta manager renders', function () {
    Livewire::test(CestaManager::class)
        ->assertSee('Gestão de Cesta Top Five')
        ->assertSee('Criar Nova Cesta');
});

test('cesta manager shows active cesta', function () {
    $cesta = Cesta::create(['nome' => 'Top Five Test', 'ativo' => true]);

    foreach ([['PETR4', 30], ['VALE3', 25], ['ITUB4', 20], ['BBDC4', 15], ['WEGE3', 10]] as $a) {
        CestaAtivo::create(['cesta_id' => $cesta->id, 'ticker' => $a[0], 'percentual' => $a[1]]);
    }

    Livewire::test(CestaManager::class)
        ->assertSee('Top Five Test')
        ->assertSee('PETR4')
        ->assertSee('Alterar Cesta');
});

test('compras panel renders', function () {
    Livewire::test(ComprasPanel::class)
        ->assertSee('Compras Programadas');
});

test('conta master panel renders', function () {
    Livewire::test(ContaMasterPanel::class)
        ->assertSee('Conta Master');
});
