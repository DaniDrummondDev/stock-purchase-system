<?php

namespace App\Domain\PurchaseEngine\ValueObjects;

final class ConsolidacaoResult
{
    /**
     * @param  array<string, int>  $aportesPorCliente  clienteId => aporte em centavos
     * @param  OrdemCompra[]  $ordens  ordens de compra (padrão + fracionário)
     * @param  array<string, int>  $quantidadesDisponiveis  ticker => total disponível (compradas + master)
     */
    public function __construct(
        public readonly int $valorTotal,
        public readonly array $aportesPorCliente,
        public readonly array $ordens,
        public readonly array $quantidadesDisponiveis,
    ) {}
}
