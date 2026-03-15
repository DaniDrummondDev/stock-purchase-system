<?php

namespace App\Application\Commands;

final class AderirClienteCommand
{
    public function __construct(
        public readonly string $nome,
        public readonly string $cpf,
        public readonly string $email,
        public readonly float $valorMensal,
    ) {}
}
