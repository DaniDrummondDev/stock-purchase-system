<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

final readonly class DataResult
{
    public function __construct(
        public array $data,
        public string $providerName,
        public bool $fromCache,
        public \DateTimeImmutable $fetchedAt,
    ) {}
}
