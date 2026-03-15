<?php

namespace App\Domain\Rebalancing\Events;

class RebalanceamentoTipoA
{
    public function __construct(
        public readonly string $clienteId,
        public readonly string $cestaAnteriorId,
        public readonly string $cestaNovaId,
        public readonly array $vendas,
        public readonly array $compras,
    ) {}
}
