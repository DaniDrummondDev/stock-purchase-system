<?php

namespace App\Application\Queries;

final class ObterCarteiraQuery
{
    public function __construct(
        public readonly string $clienteId,
    ) {}
}
