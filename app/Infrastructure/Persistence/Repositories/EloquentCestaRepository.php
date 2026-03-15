<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Basket\Entities\Cesta as CestaEntity;
use App\Domain\Basket\Entities\CestaAtivo as CestaAtivoEntity;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Basket\ValueObjects\Percentual;
use App\Domain\Basket\ValueObjects\Ticker;
use App\Infrastructure\Persistence\Models\Cesta as CestaModel;
use App\Infrastructure\Persistence\Models\CestaAtivo as CestaAtivoModel;
use Illuminate\Support\Str;

class EloquentCestaRepository implements CestaRepositoryInterface
{
    public function findById(string $id): ?CestaEntity
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        $model = CestaModel::with('ativos')->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findAtiva(): ?CestaEntity
    {
        $model = CestaModel::with('ativos')->ativa()->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(CestaEntity $cesta): void
    {
        $model = CestaModel::find($cesta->id()) ?? new CestaModel;
        $model->id = $cesta->id();
        $model->fill([
            'nome' => $cesta->nome(),
            'ativo' => $cesta->isAtiva(),
            'data_desativacao' => $cesta->dataDesativacao(),
        ]);
        $model->save();

        $model->ativos()->delete();

        foreach ($cesta->ativos() as $ativo) {
            $ativoModel = new CestaAtivoModel;
            $ativoModel->id = $ativo->id();
            $ativoModel->fill([
                'cesta_id' => $cesta->id(),
                'ticker' => $ativo->ticker()->value(),
                'percentual' => $ativo->percentual()->toDecimalString(),
            ]);
            $ativoModel->save();
        }
    }

    /**
     * @return CestaEntity[]
     */
    public function findAll(): array
    {
        return CestaModel::with('ativos')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (CestaModel $model) => $this->toEntity($model))
            ->all();
    }

    private function toEntity(CestaModel $model): CestaEntity
    {
        $ativos = $model->ativos->map(function (CestaAtivoModel $ativoModel) {
            return new CestaAtivoEntity(
                id: $ativoModel->id,
                ticker: new Ticker($ativoModel->ticker),
                percentual: new Percentual((float) $ativoModel->percentual),
            );
        })->all();

        return new CestaEntity(
            id: $model->id,
            nome: $model->nome,
            ativos: $ativos,
            ativo: $model->ativo,
            dataDesativacao: $model->data_desativacao
                ? \DateTimeImmutable::createFromMutable($model->data_desativacao->toDateTime())
                : null,
            createdAt: \DateTimeImmutable::createFromMutable($model->created_at->toDateTime()),
        );
    }
}
