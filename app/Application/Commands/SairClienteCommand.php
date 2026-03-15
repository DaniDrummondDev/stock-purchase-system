<?php

namespace App\Application\Commands;

final class SairClienteCommand
{
    public function __construct(
        public readonly string $clienteId,
    ) {}
}
