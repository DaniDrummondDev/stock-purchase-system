<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

interface FinanceAgentInterface
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * JSON Schema array for tool calling.
     *
     * @return array<string, mixed>
     */
    public function getParameterSchema(): array;

    public function execute(AgentContext $context): AgentResult;
}
