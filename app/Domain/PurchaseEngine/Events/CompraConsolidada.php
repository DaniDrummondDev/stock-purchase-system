<?php

namespace App\Domain\PurchaseEngine\Events;

class CompraConsolidada
{
    public function __construct(
        public readonly string $compraId,
        public readonly string $dataExecucao,
        public readonly float $valorTotal,
        public readonly int $totalClientes,
    ) {}
}
