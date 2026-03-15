<?php

namespace App\Domain\Basket\ValueObjects;

use InvalidArgumentException;

final class Percentual
{
    private float $value;

    public function __construct(float $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('Percentual deve ser maior que 0');
        }

        if ($value > 100) {
            throw new InvalidArgumentException('Percentual não pode ser maior que 100');
        }

        $this->value = round($value, 2);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function toDecimalString(): string
    {
        return number_format($this->value, 2, '.', '');
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.001;
    }
}
