<?php

namespace App\Domain\Tax\Events;

class IRVendaCalculado
{
    public function __construct(
        public readonly string $clienteId,
        public readonly string $cpf,
        public readonly string $mesReferencia,
        public readonly float $totalVendas,
        public readonly bool $isento,
        public readonly float $lucroLiquido,
        public readonly float $valorIR,
        public readonly array $detalhes,
    ) {}
}
