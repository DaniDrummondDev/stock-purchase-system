<?php

namespace App\Domain\PurchaseEngine\Repositories;

use App\Infrastructure\Persistence\Models\CompraProgramada;

interface CompraProgramadaRepositoryInterface
{
    public function findByData(\DateTimeImmutable $data): ?CompraProgramada;

    public function save(CompraProgramada $compra): void;

    /**
     * @return CompraProgramada[]
     */
    public function findAll(): array;

    public function findById(string $id): ?CompraProgramada;
}
