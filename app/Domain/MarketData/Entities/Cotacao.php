<?php

namespace App\Domain\MarketData\Entities;

use InvalidArgumentException;

class Cotacao
{
    public function __construct(
        private string $ticker,
        private \DateTimeImmutable $dataPregao,
        private float $precoFechamento,
        private float $precoAbertura,
        private float $precoMaximo,
        private float $precoMinimo,
        private string $tipoMercado,
        private string $codBdi,
        private float $volume = 0.0,
    ) {
        $ticker = strtoupper(trim($ticker));

        if ($ticker === '') {
            throw new InvalidArgumentException('Ticker não pode ser vazio');
        }

        if (! in_array($tipoMercado, ['padrao', 'fracionario'], true)) {
            throw new InvalidArgumentException("Tipo de mercado inválido: {$tipoMercado}");
        }

        $this->ticker = $ticker;
    }

    public function ticker(): string
    {
        return $this->ticker;
    }

    public function dataPregao(): \DateTimeImmutable
    {
        return $this->dataPregao;
    }

    public function precoFechamento(): float
    {
        return $this->precoFechamento;
    }

    public function precoAbertura(): float
    {
        return $this->precoAbertura;
    }

    public function precoMaximo(): float
    {
        return $this->precoMaximo;
    }

    public function precoMinimo(): float
    {
        return $this->precoMinimo;
    }

    public function tipoMercado(): string
    {
        return $this->tipoMercado;
    }

    public function codBdi(): string
    {
        return $this->codBdi;
    }

    public function volume(): float
    {
        return $this->volume;
    }
}
