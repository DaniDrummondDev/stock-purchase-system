<?php

namespace App\Domain\Client\Repositories;

use App\Domain\Client\Entities\Custodia;

interface CustodiaRepositoryInterface
{
    public function findByClienteId(string $clienteId): array;

    public function findByClienteIdAndTicker(string $clienteId, string $ticker): ?Custodia;

    public function save(Custodia $custodia): void;
}
