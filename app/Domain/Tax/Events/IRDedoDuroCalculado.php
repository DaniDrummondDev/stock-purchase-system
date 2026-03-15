<?php

namespace App\Domain\Tax\Events;

class IRDedoDuroCalculado
{
    public function __construct(
        public readonly string $clienteId,
        public readonly string $cpf,
        public readonly string $ticker,
        public readonly int $quantidade,
        public readonly float $precoUnitario,
        public readonly float $valorOperacao,
        public readonly float $valorIR,
        public readonly string $dataOperacao,
    ) {}
}
