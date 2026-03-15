<?php

namespace App\Application\Commands;

final class AlterarCestaCommand
{
    public function __construct(
        public readonly string $nome,
        public readonly array $ativos,
    ) {}
}
