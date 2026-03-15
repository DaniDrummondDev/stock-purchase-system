<?php

namespace App\Application\Commands;

final class AlterarValorMensalCommand
{
    public function __construct(
        public readonly string $clienteId,
        public readonly float $valorMensal,
    ) {}
}
