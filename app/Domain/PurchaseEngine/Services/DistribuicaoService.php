<?php

namespace App\Domain\PurchaseEngine\Services;

use App\Domain\PurchaseEngine\ValueObjects\DistribuicaoResult;

class DistribuicaoService
{
    /**
     * RN-034 to RN-039: Distribui ações proporcionalmente ao aporte de cada cliente.
     *
     * @param  array<string, int>  $aportesPorCliente  clienteId => aporte em centavos
     * @param  array<string, int>  $quantidadesDisponiveis  ticker => total disponível
     * @param  array<string, float>  $precosPorTicker  ticker => preço unitário
     */
    public function distribuir(
        array $aportesPorCliente,
        array $quantidadesDisponiveis,
        array $precosPorTicker,
    ): DistribuicaoResult {
        $totalAportes = array_sum($aportesPorCliente);

        if ($totalAportes <= 0) {
            return new DistribuicaoResult(alocacoes: [], residuos: $quantidadesDisponiveis);
        }

        $alocacoes = [];
        $residuos = [];

        foreach ($quantidadesDisponiveis as $ticker => $quantidadeTotal) {
            if ($quantidadeTotal <= 0) {
                continue;
            }

            $totalDistribuido = 0;
            $preco = $precosPorTicker[$ticker] ?? 0;

            foreach ($aportesPorCliente as $clienteId => $aporte) {
                // RN-035: Proporção = aporte / total
                $proporcao = $aporte / $totalAportes;

                // RN-036: TRUNCAR(proporção × quantidade)
                $qtdCliente = (int) floor($quantidadeTotal * $proporcao);

                if ($qtdCliente > 0) {
                    $alocacoes[] = [
                        'clienteId' => $clienteId,
                        'ticker' => $ticker,
                        'quantidade' => $qtdCliente,
                        'preco' => $preco,
                    ];

                    $totalDistribuido += $qtdCliente;
                }
            }

            // RN-039: Resíduo para master
            $residuo = $quantidadeTotal - $totalDistribuido;

            if ($residuo > 0) {
                $residuos[$ticker] = $residuo;
            }
        }

        return new DistribuicaoResult(
            alocacoes: $alocacoes,
            residuos: $residuos,
        );
    }
}
