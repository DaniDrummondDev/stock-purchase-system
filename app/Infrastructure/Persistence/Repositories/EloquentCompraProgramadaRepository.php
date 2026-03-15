<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\PurchaseEngine\Repositories\CompraProgramadaRepositoryInterface;
use App\Infrastructure\Persistence\Models\CompraProgramada;
use Illuminate\Support\Str;

class EloquentCompraProgramadaRepository implements CompraProgramadaRepositoryInterface
{
    public function findByData(\DateTimeImmutable $data): ?CompraProgramada
    {
        return CompraProgramada::where('data_execucao', $data->format('Y-m-d'))->first();
    }

    public function save(CompraProgramada $compra): void
    {
        $compra->save();
    }

    /**
     * @return CompraProgramada[]
     */
    public function findAll(): array
    {
        return CompraProgramada::with(['participantes', 'distribuicoes'])
            ->orderBy('data_execucao', 'desc')
            ->get()
            ->all();
    }

    public function findById(string $id): ?CompraProgramada
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        return CompraProgramada::with(['participantes', 'distribuicoes'])->find($id);
    }
}
