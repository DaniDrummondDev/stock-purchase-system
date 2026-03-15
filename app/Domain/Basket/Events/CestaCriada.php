<?php

namespace App\Domain\Basket\Events;

class CestaCriada
{
    public function __construct(
        public readonly string $cestaId,
        public readonly string $nome,
        public readonly array $ativos,
    ) {}
}
