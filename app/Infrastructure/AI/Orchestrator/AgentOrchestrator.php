<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Orchestrator;

use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\AI\Contracts\TriggerType;
use App\Domain\AI\Orchestrator\OrchestratorPromptBuilder;
use App\Infrastructure\AI\AiConfigResolver;
use App\Infrastructure\AI\Safety\AgentTimeoutConfig;
use App\Infrastructure\AI\Safety\SafeAgentExecutor;
use App\Infrastructure\AI\Tools\FinanceAgentTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class AgentOrchestrator implements Agent, HasTools
{
    use Promptable;

    /** @var FinanceAgentInterface[] */
    private array $agents;

    private string $clienteId;

    private TriggerType $triggerType;

    public function __construct(
        private readonly OrchestratorPromptBuilder $promptBuilder,
        private readonly AiConfigResolver $configResolver,
        private readonly SafeAgentExecutor $executor,
        private readonly AgentTimeoutConfig $timeoutConfig,
    ) {}

    public function forCliente(string $clienteId, TriggerType $triggerType = TriggerType::Chat): static
    {
        $instance = clone $this;
        $instance->clienteId = $clienteId;
        $instance->triggerType = $triggerType;

        return $instance;
    }

    public function withAgents(array $agents): static
    {
        $instance = clone $this;
        $instance->agents = $agents;

        return $instance;
    }

    public function instructions(): Stringable|string
    {
        $descriptions = [];
        foreach ($this->agents as $agent) {
            $descriptions[$agent->getName()] = $agent->getDescription();
        }

        return $this->promptBuilder->buildSystemPrompt($descriptions);
    }

    /**
     * @return FinanceAgentTool[]
     */
    public function tools(): iterable
    {
        return array_map(
            fn (FinanceAgentInterface $agent) => new FinanceAgentTool(
                agent: $agent,
                executor: $this->executor,
                clienteId: $this->clienteId ?? '',
                triggerType: $this->triggerType ?? TriggerType::Chat,
            ),
            $this->agents ?? [],
        );
    }

    public function provider(): string
    {
        return $this->configResolver->resolveProviderName(
            'llm',
            $this->clienteId ?? null,
        );
    }

    public function timeout(): int
    {
        return $this->timeoutConfig->defaultTimeout();
    }
}
