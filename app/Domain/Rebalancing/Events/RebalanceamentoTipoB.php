<?php

namespace App\Domain\Rebalancing\Events;

class RebalanceamentoTipoB
{
    public function __construct(
        public readonly string $clienteId,
        public readonly array $desvios,
        public readonly array $vendas,
        public readonly array $compras,
    ) {}
}
