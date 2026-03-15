<?php

namespace App\Application\Handlers;

use App\Application\Commands\AlterarCestaCommand;
use App\Domain\Basket\Entities\Cesta;
use App\Domain\Basket\Entities\CestaAtivo;
use App\Domain\Basket\Events\CestaAlterada;
use App\Domain\Basket\Repositories\CestaRepositoryInterface;
use App\Domain\Basket\ValueObjects\Percentual;
use App\Domain\Basket\ValueObjects\Ticker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AlterarCestaHandler
{
    public function __construct(
        private CestaRepositoryInterface $cestaRepository,
    ) {}

    public function handle(AlterarCestaCommand $command): array
    {
        $cestaAtual = $this->cestaRepository->findAtiva();

        if (! $cestaAtual) {
            throw new \DomainException('CESTA_NAO_ENCONTRADA');
        }

        $cestaAtual->desativar();

        $ativos = array_map(
            fn (array $a) => new CestaAtivo(
                id: (string) Str::uuid(),
                ticker: new Ticker($a['ticker']),
                percentual: new Percentual($a['percentual']),
            ),
            $command->ativos,
        );

        $novaCesta = new Cesta(
            id: (string) Str::uuid(),
            nome: $command->nome,
            ativos: $ativos,
        );

        DB::transaction(function () use ($cestaAtual, $novaCesta) {
            $this->cestaRepository->save($cestaAtual);
            $this->cestaRepository->save($novaCesta);
        });

        $diff = $this->computeDiff($cestaAtual, $novaCesta);

        event(new CestaAlterada(
            cestaAnteriorId: $cestaAtual->id(),
            cestaNovaId: $novaCesta->id(),
            ativosRemovidos: $diff['removidos'],
            ativosAdicionados: $diff['adicionados'],
            ativosAlterados: $diff['alterados'],
        ));

        return ['cestaId' => $novaCesta->id()];
    }

    private function computeDiff(Cesta $antiga, Cesta $nova): array
    {
        $tickersAntigos = $antiga->tickers();
        $tickersNovos = $nova->tickers();

        $removidos = array_values(array_diff($tickersAntigos, $tickersNovos));
        $adicionados = array_values(array_diff($tickersNovos, $tickersAntigos));

        $alterados = [];
        foreach (array_intersect($tickersAntigos, $tickersNovos) as $ticker) {
            $percentualAntigo = $antiga->percentualPorTicker($ticker);
            $percentualNovo = $nova->percentualPorTicker($ticker);

            if (abs($percentualAntigo - $percentualNovo) > 0.01) {
                $alterados[] = [
                    'ticker' => $ticker,
                    'de' => $percentualAntigo,
                    'para' => $percentualNovo,
                ];
            }
        }

        return [
            'removidos' => $removidos,
            'adicionados' => $adicionados,
            'alterados' => $alterados,
        ];
    }
}
