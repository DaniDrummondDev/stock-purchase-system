<?php

namespace App\Application\Commands;

final class ExecutarRebalanceamentoCommand
{
    public function __construct(
        public readonly string $tipo,
        public readonly ?string $clienteId = null,
    ) {}
}
