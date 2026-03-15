<?php

namespace App\Domain\PurchaseEngine\Events;

class CompraDistribuida
{
    public function __construct(
        public readonly string $compraId,
        public readonly string $clienteId,
        public readonly string $ticker,
        public readonly int $quantidade,
        public readonly float $precoUnitario,
    ) {}
}
