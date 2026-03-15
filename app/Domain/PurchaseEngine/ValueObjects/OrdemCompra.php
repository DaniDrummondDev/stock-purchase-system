<?php

namespace App\Domain\PurchaseEngine\ValueObjects;

final class OrdemCompra
{
    public function __construct(
        public readonly string $ticker,
        public readonly int $quantidade,
        public readonly float $preco,
        public readonly string $tipoLote,
    ) {}
}
