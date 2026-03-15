<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Client\Entities\Custodia as CustodiaEntity;
use App\Domain\Client\Repositories\CustodiaRepositoryInterface;
use App\Domain\Client\ValueObjects\Money;
use App\Infrastructure\Persistence\Models\Custodia as CustodiaModel;

class EloquentCustodiaRepository implements CustodiaRepositoryInterface
{
    public function findByClienteId(string $clienteId): array
    {
        return CustodiaModel::where('cliente_id', $clienteId)
            ->get()
            ->map(fn (CustodiaModel $model) => $this->toEntity($model))
            ->all();
    }

    public function findByClienteIdAndTicker(string $clienteId, string $ticker): ?CustodiaEntity
    {
        $model = CustodiaModel::where('cliente_id', $clienteId)
            ->where('ticker', $ticker)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(CustodiaEntity $custodia): void
    {
        $model = CustodiaModel::find($custodia->id()) ?? new CustodiaModel;
        $model->id = $custodia->id();
        $model->fill([
            'cliente_id' => $custodia->clienteId(),
            'ticker' => $custodia->ticker(),
            'quantidade' => $custodia->quantidade(),
            'preco_medio' => $custodia->precoMedio()->toDecimalString(),
        ]);
        $model->save();
    }

    private function toEntity(CustodiaModel $model): CustodiaEntity
    {
        return new CustodiaEntity(
            id: $model->id,
            clienteId: $model->cliente_id,
            ticker: $model->ticker,
            quantidade: $model->quantidade,
            precoMedio: Money::fromDecimal($model->preco_medio),
        );
    }
}
