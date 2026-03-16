<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Safety;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\AgentResult;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Infrastructure\Persistence\Models\AgentExecution;

class SafeAgentExecutor
{
    public function __construct(
        private readonly AgentCircuitBreaker $circuitBreaker,
        private readonly AgentTimeoutConfig $timeoutConfig,
    ) {}

    public function execute(FinanceAgentInterface $agent, AgentContext $context): AgentResult
    {
        $agentName = $agent->getName();

        if ($this->circuitBreaker->isOpen($agentName)) {
            $this->logExecution($agentName, $context, null, 'circuit_open', 'Circuit breaker is open');

            return new AgentResult(
                data: [],
                summary: "Agente {$agentName} temporariamente indisponível. Tente novamente em alguns minutos.",
                confidence: 0.0,
                metadata: ['circuit_breaker' => 'open'],
            );
        }

        $startTime = hrtime(true);
        $execution = $this->logExecution($agentName, $context, null, 'running');

        try {
            $result = $agent->execute($context);

            $elapsedMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            $this->circuitBreaker->recordSuccess($agentName);

            $execution->update([
                'status' => 'completed',
                'result_data' => $result->data,
                'confidence' => $result->confidence,
                'execution_time_ms' => $elapsedMs,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $elapsedMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            $this->circuitBreaker->recordFailure($agentName);

            $execution->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 1000),
                'execution_time_ms' => $elapsedMs,
            ]);

            return new AgentResult(
                data: [],
                summary: "Erro ao executar agente {$agentName}: {$e->getMessage()}",
                confidence: 0.0,
                metadata: ['error' => $e->getMessage(), 'error_class' => get_class($e)],
            );
        }
    }

    private function logExecution(
        string $agentName,
        AgentContext $context,
        ?AgentResult $result,
        string $status,
        ?string $errorMessage = null,
    ): AgentExecution {
        return AgentExecution::create([
            'cliente_id' => $context->clienteId,
            'agent_name' => $agentName,
            'trigger_type' => $context->triggerType->value,
            'input_context' => [
                'request' => $context->request,
                'additional_params' => $context->additionalParams,
            ],
            'result_data' => $result?->data,
            'confidence' => $result?->confidence,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }
}
