<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Client\Entities\ContaGrafica as ContaGraficaEntity;
use App\Domain\Client\Repositories\ContaGraficaRepositoryInterface;
use App\Infrastructure\Persistence\Models\ContaGrafica as ContaGraficaModel;

class EloquentContaGraficaRepository implements ContaGraficaRepositoryInterface
{
    public function findByClienteId(string $clienteId): ?ContaGraficaEntity
    {
        $model = ContaGraficaModel::where('cliente_id', $clienteId)->first();

        return $model ? new ContaGraficaEntity($model->id, $model->cliente_id, $model->numero) : null;
    }

    public function save(ContaGraficaEntity $contaGrafica): void
    {
        $model = ContaGraficaModel::find($contaGrafica->id()) ?? new ContaGraficaModel();
        $model->id = $contaGrafica->id();
        $model->fill([
            'cliente_id' => $contaGrafica->clienteId(),
            'numero' => $contaGrafica->numero(),
        ]);
        $model->save();
    }
}
