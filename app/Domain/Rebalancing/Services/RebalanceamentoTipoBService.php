<?php

namespace App\Domain\Rebalancing\Services;

class RebalanceamentoTipoBService
{
    private const LIMIAR_DESVIO_PP = 5.0; // 5 pontos percentuais

    /**
     * RN-050 to RN-052: Detecta desvios e calcula rebalanceamento.
     *
     * @param  array  $custodias  [ticker => ['quantidade' => int, 'precoMedio' => float], ...]
     * @param  array<string, float>  $percentuaisAlvo  ticker => percentual alvo (ex: 30.0)
     * @param  array<string, float>  $cotacoes  ticker => preço fechamento
     * @param  float  $limiar  limiar em pp (default 5.0)
     * @return array ['necessario' => bool, 'desvios' => [...], 'vendas' => [...], 'compras' => [...]]
     */
    public function analisar(
        array $custodias,
        array $percentuaisAlvo,
        array $cotacoes,
        float $limiar = self::LIMIAR_DESVIO_PP,
    ): array {
        $valorTotal = 0;

        foreach ($custodias as $ticker => $custodia) {
            $preco = $cotacoes[$ticker] ?? 0;
            $valorTotal += $custodia['quantidade'] * $preco;
        }

        if ($valorTotal <= 0) {
            return ['necessario' => false, 'desvios' => [], 'vendas' => [], 'compras' => []];
        }

        $desvios = [];
        $necessario = false;

        foreach ($percentuaisAlvo as $ticker => $alvo) {
            $custodia = $custodias[$ticker] ?? null;
            $preco = $cotacoes[$ticker] ?? 0;

            $valorAtual = ($custodia ? $custodia['quantidade'] * $preco : 0);
            $percentualReal = ($valorTotal > 0) ? ($valorAtual / $valorTotal) * 100 : 0;
            $desvio = $percentualReal - $alvo;

            $desvios[$ticker] = [
                'alvo' => $alvo,
                'real' => round($percentualReal, 2),
                'desvio' => round($desvio, 2),
            ];

            if (abs($desvio) > $limiar) {
                $necessario = true;
            }
        }

        if (! $necessario) {
            return ['necessario' => false, 'desvios' => $desvios, 'vendas' => [], 'compras' => []];
        }

        // Calcular operações de rebalanceamento
        $vendas = [];
        $compras = [];

        foreach ($desvios as $ticker => $info) {
            if (abs($info['desvio']) <= $limiar) {
                continue;
            }

            $preco = $cotacoes[$ticker] ?? 0;

            if ($preco <= 0) {
                continue;
            }

            $custodia = $custodias[$ticker] ?? null;
            $valorAtual = ($custodia ? $custodia['quantidade'] * $preco : 0);
            $valorAlvo = $valorTotal * ($info['alvo'] / 100);
            $diferenca = $valorAlvo - $valorAtual;
            $qtd = (int) floor(abs($diferenca) / $preco);

            if ($qtd <= 0) {
                continue;
            }

            if ($diferenca < 0) {
                $vendas[] = [
                    'ticker' => $ticker,
                    'quantidade' => $qtd,
                    'preco' => $preco,
                    'precoMedio' => $custodia['precoMedio'] ?? 0,
                    'valor' => round($qtd * $preco, 2),
                ];
            } else {
                $compras[] = [
                    'ticker' => $ticker,
                    'quantidade' => $qtd,
                    'preco' => $preco,
                    'valor' => round($qtd * $preco, 2),
                ];
            }
        }

        return [
            'necessario' => true,
            'desvios' => $desvios,
            'vendas' => $vendas,
            'compras' => $compras,
        ];
    }
}
