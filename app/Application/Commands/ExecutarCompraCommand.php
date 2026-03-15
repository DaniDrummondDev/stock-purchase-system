<?php

namespace App\Application\Commands;

final class ExecutarCompraCommand
{
    public function __construct(
        public readonly string $dataExecucao,
    ) {}
}
