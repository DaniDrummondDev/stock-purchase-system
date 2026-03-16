<?php

declare(strict_types=1);

namespace App\Domain\AI\Orchestrator;

class ExecutionPlan
{
    /** @var array<int, array{agent: string, params: array, started_at: ?float, completed_at: ?float}> */
    private array $steps = [];

    public function addStep(string $agentName, array $params = []): void
    {
        $this->steps[] = [
            'agent' => $agentName,
            'params' => $params,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function markStarted(string $agentName): void
    {
        foreach ($this->steps as &$step) {
            if ($step['agent'] === $agentName && $step['started_at'] === null) {
                $step['started_at'] = microtime(true);
                break;
            }
        }
    }

    public function markCompleted(string $agentName): void
    {
        foreach ($this->steps as &$step) {
            if ($step['agent'] === $agentName && $step['completed_at'] === null) {
                $step['completed_at'] = microtime(true);
                break;
            }
        }
    }

    public function agentNames(): array
    {
        return array_column($this->steps, 'agent');
    }

    public function toArray(): array
    {
        return $this->steps;
    }

    public function totalExecutionTimeMs(): int
    {
        $earliest = null;
        $latest = null;

        foreach ($this->steps as $step) {
            if ($step['started_at'] !== null) {
                $earliest = $earliest === null ? $step['started_at'] : min($earliest, $step['started_at']);
            }
            if ($step['completed_at'] !== null) {
                $latest = $latest === null ? $step['completed_at'] : max($latest, $step['completed_at']);
            }
        }

        if ($earliest === null || $latest === null) {
            return 0;
        }

        return (int) (($latest - $earliest) * 1000);
    }
}
