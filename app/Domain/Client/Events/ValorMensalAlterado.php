<?php

namespace App\Domain\Client\Events;

class ValorMensalAlterado
{
    public function __construct(
        public readonly string $clienteId,
        public readonly string $valorAnterior,
        public readonly string $valorNovo,
    ) {}
}
