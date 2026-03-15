<?php

namespace App\Application\Queries;

final class ObterCotacaoQuery
{
    public function __construct(
        public readonly string $ticker,
        public readonly ?string $data = null,
    ) {}
}
