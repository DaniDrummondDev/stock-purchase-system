<?php

namespace App\Application\Handlers;

use App\Application\Queries\ObterHistoricoCestaQuery;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;

class ObterHistoricoCestaHandler
{
    public function __construct(
        private CestaRepositoryInterface $cestaRepository,
    ) {}

    public function handle(ObterHistoricoCestaQuery $query): array
    {
        $cestas = $this->cestaRepository->findAll();

        return array_map(fn ($cesta) => [
            'id' => $cesta->id(),
            'nome' => $cesta->nome(),
            'ativo' => $cesta->isAtiva(),
            'dataDesativacao' => $cesta->dataDesativacao()?->format('Y-m-d H:i:s'),
            'ativos' => array_map(fn ($a) => [
                'ticker' => $a->ticker()->value(),
                'percentual' => $a->percentual()->toDecimalString(),
            ], $cesta->ativos()),
            'createdAt' => $cesta->createdAt()->format('Y-m-d H:i:s'),
        ], $cestas);
    }
}
