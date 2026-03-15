<?php

namespace App\Domain\Basket\Entities;

use App\Domain\Basket\ValueObjects\Percentual;
use App\Domain\Basket\ValueObjects\Ticker;

class CestaAtivo
{
    public function __construct(
        private string $id,
        private Ticker $ticker,
        private Percentual $percentual,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function ticker(): Ticker
    {
        return $this->ticker;
    }

    public function percentual(): Percentual
    {
        return $this->percentual;
    }
}
