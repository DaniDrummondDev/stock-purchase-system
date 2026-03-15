<?php

namespace App\Domain\Tax\Services;

class DedoDuroService
{
    private const ALIQUOTA = 0.00005; // 0.005%

    /**
     * RN-053/054: Calcula IR dedo-duro sobre o valor de cada operação.
     */
    public function calcular(float $valorOperacao): float
    {
        return round($valorOperacao * self::ALIQUOTA, 2);
    }

    public function aliquota(): float
    {
        return self::ALIQUOTA;
    }
}
