<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

final readonly class DataQuery
{
    public function __construct(
        public string $capability,
        public array $params = [],
        public ?int $cacheTtlSeconds = null,
    ) {}
}
