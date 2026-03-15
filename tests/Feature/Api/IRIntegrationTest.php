<?php

use App\Infrastructure\Persistence\Models\Cesta;
use App\Infrastructure\Persistence\Models\CestaAtivo;
use App\Infrastructure\Persistence\Models\Cliente;
use App\Infrastructure\Persistence\Models\Cotacao;
use App\Infrastructure\Persistence\Models\OperacaoIR;

function setupIRScenario(): void
{
    $cesta = Cesta::create(['nome' => 'Top Five', 'ativo' => true]);

    foreach ([['PETR4', 30], ['VALE3', 25], ['ITUB4', 20], ['BBDC4', 15], ['WEGE3', 10]] as $a) {
        CestaAtivo::create(['cesta_id' => $cesta->id, 'ticker' => $a[0], 'percentual' => $a[1]]);
    }

    Cliente::create([
        'nome' => 'Cliente A',
        'cpf' => '12345678909',
        'email' => 'a@test.com',
        'valor_mensal' => 3000,
        'status' => 'ativo',
        'valor_total_investido' => 0,
    ]);

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

test('compra gera registros de IR dedo-duro', function () {
    setupIRScenario();

    $response = $this->postJson('/api/admin/motor/executar-compra', [
        'dataExecucao' => '2026-02-25',
    ]);

    $response->assertStatus(201);

    // Verify IR dedo-duro records were created
    $irRecords = OperacaoIR::where('tipo', 'dedo_duro')->count();
    expect($irRecords)->toBeGreaterThan(0);

    // Verify all IR records have correct month reference
    $irRecords = OperacaoIR::all();

    foreach ($irRecords as $record) {
        expect($record->mes_referencia)->toBe('2026-02');
        expect((float) $record->imposto)->toBeGreaterThanOrEqual(0);
        expect($record->tipo)->toBe('dedo_duro');
    }
});

test('IR dedo-duro e 0.005% do valor da operacao', function () {
    setupIRScenario();

    $this->postJson('/api/admin/motor/executar-compra', [
        'dataExecucao' => '2026-02-25',
    ]);

    $irRecords = OperacaoIR::where('tipo', 'dedo_duro')->get();

    foreach ($irRecords as $record) {
        $expected = round((float) $record->valor_operacao * 0.00005, 2);
        expect((float) $record->imposto)->toBe($expected);
    }
});
