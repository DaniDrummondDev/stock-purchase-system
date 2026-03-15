<?php

namespace App\Domain\PurchaseEngine\Services;

use App\Domain\Basket\Entities\Cesta;
use App\Domain\Client\Entities\Cliente;
use App\Domain\MarketData\Entities\Cotacao;
use App\Domain\PurchaseEngine\ValueObjects\ConsolidacaoResult;
use App\Domain\PurchaseEngine\ValueObjects\OrdemCompra;

class ConsolidacaoService
{
    /**
     * RN-024 to RN-033: Consolida aportes, calcula quantidades, separa lotes.
     *
     * @param  Cliente[]  $clientes  active clients
     * @param  Cotacao[]  $cotacoes  indexed by ticker
     * @param  array<string, int>  $saldosMaster  ticker => quantity in master
     */
    public function consolidar(array $clientes, Cesta $cesta, array $cotacoes, array $saldosMaster): ConsolidacaoResult
    {
        // RN-025/026: Calculate aportes
        $aportesPorCliente = [];
        $valorTotal = 0;

        foreach ($clientes as $cliente) {
            $aporte = $cliente->valorAportePorCompra()->cents();
            $aportesPorCliente[$cliente->id()] = $aporte;
            $valorTotal += $aporte;
        }

        // Calculate value per ticker based on cesta percentages
        $ordens = [];
        $quantidadesDisponiveis = [];

        foreach ($cesta->ativos() as $ativo) {
            $ticker = $ativo->ticker()->value();
            $percentual = $ativo->percentual()->value() / 100;
            $valorTicker = (int) floor($valorTotal * $percentual);

            // RN-027: Get closing price
            $cotacao = $cotacoes[$ticker] ?? null;

            if (! $cotacao || $cotacao->precoFechamento() <= 0) {
                continue;
            }

            $precoCentavos = (int) round($cotacao->precoFechamento() * 100);

            // RN-028: TRUNCAR(valor / cotação)
            $quantidadeTotal = (int) floor($valorTicker / $precoCentavos);

            // RN-029/030: Subtract master balance
            $saldoMaster = $saldosMaster[$ticker] ?? 0;
            $quantidadeComprar = max(0, $quantidadeTotal - $saldoMaster);

            // Total disponível para distribuição = compradas + saldo master
            $quantidadesDisponiveis[$ticker] = $quantidadeComprar + $saldoMaster;

            // RN-031/032/033: Split standard vs fractional
            if ($quantidadeComprar > 0) {
                $lotePadrao = (int) floor($quantidadeComprar / 100) * 100;
                $fracionario = $quantidadeComprar - $lotePadrao;

                if ($lotePadrao > 0) {
                    $ordens[] = new OrdemCompra(
                        ticker: $ticker,
                        quantidade: $lotePadrao,
                        preco: $cotacao->precoFechamento(),
                        tipoLote: 'padrao',
                    );
                }

                if ($fracionario > 0) {
                    $ordens[] = new OrdemCompra(
                        ticker: $ticker,
                        quantidade: $fracionario,
                        preco: $cotacao->precoFechamento(),
                        tipoLote: 'fracionario',
                    );
                }
            }
        }

        return new ConsolidacaoResult(
            valorTotal: $valorTotal,
            aportesPorCliente: $aportesPorCliente,
            ordens: $ordens,
            quantidadesDisponiveis: $quantidadesDisponiveis,
        );
    }
}
