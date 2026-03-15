<?php

namespace App\Domain\Basket\ValueObjects;

use InvalidArgumentException;

final class Ticker
{
    private string $value;

    public function __construct(string $value)
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            throw new InvalidArgumentException('Ticker não pode ser vazio');
        }

        if (strlen($value) > 12) {
            throw new InvalidArgumentException('Ticker não pode ter mais de 12 caracteres');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
