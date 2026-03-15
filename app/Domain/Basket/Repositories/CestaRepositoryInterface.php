<?php

namespace App\Domain\Basket\Repositories;

use App\Domain\Basket\Entities\Cesta;

interface CestaRepositoryInterface
{
    public function findById(string $id): ?Cesta;

    public function findAtiva(): ?Cesta;

    public function save(Cesta $cesta): void;

    /**
     * @return Cesta[]
     */
    public function findAll(): array;
}
