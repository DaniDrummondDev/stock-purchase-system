# ADR-004: Agent Orchestrator Pattern

## Status
Accepted

## Context
The system requires 6 specialized finance agents that need orchestration. Three approaches were evaluated: manual state machine (rigid, hard to extend), standalone agent SDK (additional dependency, less Laravel integration), or LLM-as-maestro (flexible, adaptive).

## Decision
Adopt a hybrid approach combining Laravel infrastructure with LLM-driven decision-making:

- **Execution layer**: Laravel Jobs, Queues, and Events handle reliable task execution, retries, and failure recovery.
- **Decision layer**: LLM tool calling via `laravel/ai` determines which agents to invoke and in what order.
- **AgentOrchestrator (Maestro)**: Uses LLM to analyze context and decide which agents to call, passing structured tool definitions.
- **FinanceAgentTool**: Each specialized agent is wrapped as a `laravel/ai` Tool adapter, exposing its capabilities to the LLM.
- **Safety controls**:
  - `AgentCircuitBreaker` (Redis-backed) — prevents cascading failures by halting calls to failing agents.
  - `AgentTimeoutConfig` — enforces per-agent execution time limits.
  - `SafeAgentExecutor` — wraps agent execution with circuit breaker, timeout, and error handling.

## Consequences
### Positive
- LLM adapts orchestration dynamically based on context, avoiding rigid decision trees.
- No manual state machine to maintain — the LLM reasons about agent composition.
- Proven `laravel/ai` tooling provides structured input/output contracts for agents.
- Safety controls (circuit breaker, timeouts) prevent runaway agent execution.

### Negative
- Each orchestration call consumes LLM tokens, adding cost per decision.
- Quality of orchestration depends on the model used — weaker models may make suboptimal agent selections.
- Debugging LLM-driven decisions is harder than tracing a deterministic state machine.

### Risks
- LLM hallucination could lead to incorrect agent selection or parameter passing.
- Network latency to LLM provider adds overhead to orchestration decisions.
- Circuit breaker thresholds require tuning based on real-world failure patterns.
