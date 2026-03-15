<?php

namespace App\Domain\MarketData\Events;

class CotacoesImportadas
{
    public function __construct(
        public readonly string $filePath,
        public readonly int $totalImportadas,
        public readonly ?string $dataPregao,
    ) {}
}
