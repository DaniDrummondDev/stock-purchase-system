<?php

namespace App\Domain\Basket\Events;

class CestaAlterada
{
    public function __construct(
        public readonly string $cestaAnteriorId,
        public readonly string $cestaNovaId,
        public readonly array $ativosRemovidos,
        public readonly array $ativosAdicionados,
        public readonly array $ativosAlterados,
    ) {}
}
