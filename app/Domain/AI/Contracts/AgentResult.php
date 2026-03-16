<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

final readonly class AgentResult
{
    public function __construct(
        public array $data,
        public string $summary,
        public float $confidence,
        public array $metadata = [],
    ) {
        if ($confidence < 0.0 || $confidence > 1.0) {
            throw new \InvalidArgumentException('Confidence must be between 0.0 and 1.0');
        }
    }
}
