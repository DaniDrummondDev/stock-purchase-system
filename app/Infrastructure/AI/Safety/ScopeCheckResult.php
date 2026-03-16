<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Safety;

final readonly class ScopeCheckResult
{
    public function __construct(
        public bool $inScope,
        public string $reason,
    ) {}
}
