<?php

namespace App\Domain\Client\Repositories;

use App\Domain\Client\Entities\Cliente;

interface ClienteRepositoryInterface
{
    public function findById(string $id): ?Cliente;

    public function findByCpf(string $cpf): ?Cliente;

    public function save(Cliente $cliente): void;

    public function existsByCpf(string $cpf): bool;

    public function findAtivos(): array;
}
