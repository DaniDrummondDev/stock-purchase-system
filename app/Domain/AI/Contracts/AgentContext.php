<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

final readonly class AgentContext
{
    public function __construct(
        public string $clienteId,
        public string $request,
        public TriggerType $triggerType,
        public ?string $chatSessionId = null,
        public array $additionalParams = [],
    ) {}
}
