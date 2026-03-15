<?php

namespace App\Domain\Client\ValueObjects;

use InvalidArgumentException;

final class Money
{
    private int $cents;

    private function __construct(int $cents)
    {
        $this->cents = $cents;
    }

    public static function fromDecimal(float|string $value): self
    {
        $decimal = (float) $value;

        if ($decimal < 0) {
            throw new InvalidArgumentException("Valor monetário não pode ser negativo: {$value}");
        }

        return new self((int) round($decimal * 100));
    }

    public static function fromCents(int $cents): self
    {
        if ($cents < 0) {
            throw new InvalidArgumentException("Valor monetário não pode ser negativo: {$cents}");
        }

        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function toDecimal(): float
    {
        return $this->cents / 100;
    }

    public function toDecimalString(): string
    {
        return number_format($this->cents / 100, 2, '.', '');
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->cents * $factor));
    }

    public function divideBy(int $divisor): self
    {
        if ($divisor === 0) {
            throw new InvalidArgumentException('Divisão por zero');
        }

        return new self((int) floor($this->cents / $divisor));
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->cents >= $other->cents;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    public function __toString(): string
    {
        return $this->toDecimalString();
    }
}
