<?php

namespace App\Domain\Client\Repositories;

use App\Domain\Client\Entities\ContaGrafica;

interface ContaGraficaRepositoryInterface
{
    public function findByClienteId(string $clienteId): ?ContaGrafica;

    public function save(ContaGrafica $contaGrafica): void;
}
