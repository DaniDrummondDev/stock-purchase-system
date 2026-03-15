<?php

namespace App\Domain\Tax\Services;

class IRVendaService
{
    private const LIMITE_ISENCAO = 2000000; // R$ 20.000,00 em centavos

    private const ALIQUOTA = 0.20; // 20%

    /**
     * RN-057 to RN-061: Calcula IR sobre vendas do mês.
     *
     * @param  array  $vendas  [['ticker' => ..., 'quantidade' => ..., 'precoVenda' => ..., 'precoMedio' => ...], ...]
     * @return array ['isento' => bool, 'totalVendas' => float, 'lucroLiquido' => float, 'valorIR' => float, 'detalhes' => [...]]
     */
    public function calcular(array $vendas): array
    {
        $totalVendas = 0;
        $lucroTotal = 0;
        $detalhes = [];

        foreach ($vendas as $venda) {
            $valorVenda = $venda['quantidade'] * $venda['precoVenda'];
            $totalVendas += $valorVenda;

            $lucro = $venda['quantidade'] * ($venda['precoVenda'] - $venda['precoMedio']);

            $detalhes[] = [
                'ticker' => $venda['ticker'],
                'quantidade' => $venda['quantidade'],
                'precoVenda' => $venda['precoVenda'],
                'precoMedio' => $venda['precoMedio'],
                'valorVenda' => round($valorVenda, 2),
                'lucro' => round($lucro, 2),
            ];

            $lucroTotal += $lucro;
        }

        $totalVendas = round($totalVendas, 2);
        $lucroTotal = round($lucroTotal, 2);

        // RN-058: vendas ≤ R$20k = isento
        $isento = $totalVendas <= self::LIMITE_ISENCAO / 100;

        // RN-059/061: 20% sobre lucro, mas apenas se houver lucro positivo
        $valorIR = 0.0;

        if (! $isento && $lucroTotal > 0) {
            $valorIR = round($lucroTotal * self::ALIQUOTA, 2);
        }

        return [
            'isento' => $isento,
            'totalVendas' => $totalVendas,
            'lucroLiquido' => $lucroTotal,
            'valorIR' => $valorIR,
            'aliquota' => $isento ? 0 : self::ALIQUOTA,
            'detalhes' => $detalhes,
        ];
    }
}
