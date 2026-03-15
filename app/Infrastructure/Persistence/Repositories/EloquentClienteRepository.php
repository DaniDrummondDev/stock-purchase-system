<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Client\Entities\Cliente as ClienteEntity;
use App\Domain\Client\Repositories\ClienteRepositoryInterface;
use App\Domain\Client\ValueObjects\Cpf;
use App\Domain\Client\ValueObjects\Email;
use App\Domain\Client\ValueObjects\Money;
use App\Infrastructure\Persistence\Models\Cliente as ClienteModel;
use Illuminate\Support\Str;

class EloquentClienteRepository implements ClienteRepositoryInterface
{
    public function findById(string $id): ?ClienteEntity
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        $model = ClienteModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByCpf(string $cpf): ?ClienteEntity
    {
        $model = ClienteModel::where('cpf', $cpf)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(ClienteEntity $cliente): void
    {
        $model = ClienteModel::find($cliente->id()) ?? new ClienteModel();
        $model->id = $cliente->id();
        $model->fill([
            'nome' => $cliente->nome(),
            'cpf' => $cliente->cpf()->value(),
            'email' => $cliente->email()->value(),
            'valor_mensal' => $cliente->valorMensal()->toDecimalString(),
            'status' => $cliente->status(),
            'valor_total_investido' => $cliente->valorTotalInvestido()->toDecimalString(),
        ]);
        $model->save();
    }

    public function existsByCpf(string $cpf): bool
    {
        return ClienteModel::where('cpf', $cpf)->exists();
    }

    public function findAtivos(): array
    {
        return ClienteModel::ativos()
            ->get()
            ->map(fn (ClienteModel $model) => $this->toEntity($model))
            ->all();
    }

    private function toEntity(ClienteModel $model): ClienteEntity
    {
        $entity = new ClienteEntity(
            id: $model->id,
            nome: $model->nome,
            cpf: new Cpf($model->cpf),
            email: new Email($model->email),
            valorMensal: Money::fromDecimal($model->valor_mensal),
        );

        // Reconstituir estado
        if ($model->status === 'inativo') {
            $entity->sair();
        }

        // Reconstituir valor total investido via reflection
        $ref = new \ReflectionProperty($entity, 'valorTotalInvestido');
        $ref->setValue($entity, Money::fromDecimal($model->valor_total_investido));

        return $entity;
    }
}
