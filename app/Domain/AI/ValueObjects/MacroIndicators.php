<?php

declare(strict_types=1);

namespace App\Domain\AI\ValueObjects;

final readonly class MacroIndicators
{
    public function __construct(
        public float $selic,
        public float $ipca,
        public float $usdBrl,
        public \DateTimeImmutable $fetchedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'selic' => $this->selic,
            'ipca' => $this->ipca,
            'usd_brl' => $this->usdBrl,
            'fetched_at' => $this->fetchedAt->format('c'),
        ];
    }
}
