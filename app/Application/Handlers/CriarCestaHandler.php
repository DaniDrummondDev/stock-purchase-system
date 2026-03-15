<?php

namespace App\Application\Handlers;

use App\Application\Commands\CriarCestaCommand;
use App\Domain\Basket\Entities\Cesta;
use App\Domain\Basket\Entities\CestaAtivo;
use App\Domain\Basket\Events\CestaCriada;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Basket\ValueObjects\Percentual;
use App\Domain\Basket\ValueObjects\Ticker;
use Illuminate\Support\Str;

class CriarCestaHandler
{
    public function __construct(
        private CestaRepositoryInterface $cestaRepository,
    ) {}

    public function handle(CriarCestaCommand $command): array
    {
        if ($this->cestaRepository->findAtiva()) {
            throw new \DomainException('CESTA_JA_EXISTE');
        }

        $ativos = array_map(
            fn (array $a) => new CestaAtivo(
                id: (string) Str::uuid(),
                ticker: new Ticker($a['ticker']),
                percentual: new Percentual($a['percentual']),
            ),
            $command->ativos,
        );

        $cesta = new Cesta(
            id: (string) Str::uuid(),
            nome: $command->nome,
            ativos: $ativos,
        );

        $this->cestaRepository->save($cesta);

        event(new CestaCriada(
            cestaId: $cesta->id(),
            nome: $cesta->nome(),
            ativos: array_map(
                fn (CestaAtivo $a) => ['ticker' => $a->ticker()->value(), 'percentual' => $a->percentual()->value()],
                $cesta->ativos(),
            ),
        ));

        return ['cestaId' => $cesta->id()];
    }
}
