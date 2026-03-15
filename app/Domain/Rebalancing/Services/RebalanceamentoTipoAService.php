<?php

namespace App\Domain\Rebalancing\Services;

class RebalanceamentoTipoAService
{
    /**
     * RN-045 to RN-049: Calcula operações de rebalanceamento quando a cesta muda.
     *
     * @param  array  $custodias  [ticker => ['quantidade' => int, 'precoMedio' => float], ...]
     * @param  array<string, float>  $novosPercentuais  ticker => percentual (ex: 25.0)
     * @param  array<string, float>  $cotacoes  ticker => preço fechamento
     * @param  string[]  $ativosRemovidos  tickers que saíram da cesta
     * @param  string[]  $ativosAdicionados  tickers que entraram na cesta
     * @return array ['vendas' => [...], 'compras' => [...]]
     */
    public function calcular(
        array $custodias,
        array $novosPercentuais,
        array $cotacoes,
        array $ativosRemovidos,
        array $ativosAdicionados,
    ): array {
        $vendas = [];
        $compras = [];

        // RN-046/047: Vender toda posição dos ativos que saíram
        $valorObtidoVendas = 0;

        foreach ($ativosRemovidos as $ticker) {
            $custodia = $custodias[$ticker] ?? null;

            if (! $custodia || $custodia['quantidade'] <= 0) {
                continue;
            }

            $preco = $cotacoes[$ticker] ?? 0;

            if ($preco <= 0) {
                continue;
            }

            $vendas[] = [
                'ticker' => $ticker,
                'quantidade' => $custodia['quantidade'],
                'preco' => $preco,
                'precoMedio' => $custodia['precoMedio'],
                'valor' => round($custodia['quantidade'] * $preco, 2),
                'tipo' => 'remocao',
            ];

            $valorObtidoVendas += $custodia['quantidade'] * $preco;
        }

        // RN-048: Comprar novos ativos com o valor das vendas
        if ($valorObtidoVendas > 0 && ! empty($ativosAdicionados)) {
            $totalPercentualNovos = 0;

            foreach ($ativosAdicionados as $ticker) {
                $totalPercentualNovos += $novosPercentuais[$ticker] ?? 0;
            }

            if ($totalPercentualNovos > 0) {
                foreach ($ativosAdicionados as $ticker) {
                    $percentual = $novosPercentuais[$ticker] ?? 0;
                    $preco = $cotacoes[$ticker] ?? 0;

                    if ($percentual <= 0 || $preco <= 0) {
                        continue;
                    }

                    $proporcao = $percentual / $totalPercentualNovos;
                    $valorCompra = $valorObtidoVendas * $proporcao;
                    $quantidade = (int) floor($valorCompra / $preco);

                    if ($quantidade > 0) {
                        $compras[] = [
                            'ticker' => $ticker,
                            'quantidade' => $quantidade,
                            'preco' => $preco,
                            'valor' => round($quantidade * $preco, 2),
                            'tipo' => 'adicao',
                        ];
                    }
                }
            }
        }

        // RN-049: Rebalancear ativos que mudaram de percentual
        $valorTotalCarteira = 0;

        foreach ($custodias as $ticker => $custodia) {
            $preco = $cotacoes[$ticker] ?? 0;
            $valorTotalCarteira += $custodia['quantidade'] * $preco;
        }

        foreach ($novosPercentuais as $ticker => $percentual) {
            if (in_array($ticker, $ativosRemovidos, true) || in_array($ticker, $ativosAdicionados, true)) {
                continue;
            }

            $custodia = $custodias[$ticker] ?? null;

            if (! $custodia || $custodia['quantidade'] <= 0) {
                continue;
            }

            $preco = $cotacoes[$ticker] ?? 0;

            if ($preco <= 0) {
                continue;
            }

            $valorAtual = $custodia['quantidade'] * $preco;
            $valorAlvo = $valorTotalCarteira * ($percentual / 100);
            $diferenca = $valorAlvo - $valorAtual;
            $qtdDiferenca = (int) floor(abs($diferenca) / $preco);

            if ($qtdDiferenca <= 0) {
                continue;
            }

            if ($diferenca < 0) {
                // Sobre-alocado → vender
                $vendas[] = [
                    'ticker' => $ticker,
                    'quantidade' => $qtdDiferenca,
                    'preco' => $preco,
                    'precoMedio' => $custodia['precoMedio'],
                    'valor' => round($qtdDiferenca * $preco, 2),
                    'tipo' => 'rebalanceamento',
                ];
            } else {
                // Sub-alocado → comprar
                $compras[] = [
                    'ticker' => $ticker,
                    'quantidade' => $qtdDiferenca,
                    'preco' => $preco,
                    'valor' => round($qtdDiferenca * $preco, 2),
                    'tipo' => 'rebalanceamento',
                ];
            }
        }

        return [
            'vendas' => $vendas,
            'compras' => $compras,
        ];
    }
}
