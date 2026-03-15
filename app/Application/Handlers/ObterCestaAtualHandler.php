<?php

namespace App\Application\Handlers;

use App\Application\Queries\ObterCestaAtualQuery;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;

class ObterCestaAtualHandler
{
    public function __construct(
        private CestaRepositoryInterface $cestaRepository,
    ) {}

    public function handle(ObterCestaAtualQuery $query): array
    {
        $cesta = $this->cestaRepository->findAtiva();

        if (! $cesta) {
            throw new \DomainException('CESTA_NAO_ENCONTRADA');
        }

        return [
            'id' => $cesta->id(),
            'nome' => $cesta->nome(),
            'ativo' => $cesta->isAtiva(),
            'ativos' => array_map(fn ($a) => [
                'ticker' => $a->ticker()->value(),
                'percentual' => $a->percentual()->toDecimalString(),
            ], $cesta->ativos()),
            'createdAt' => $cesta->createdAt()->format('Y-m-d H:i:s'),
        ];
    }
}
