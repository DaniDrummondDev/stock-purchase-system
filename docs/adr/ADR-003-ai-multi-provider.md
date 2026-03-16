# ADR-003: AI Multi-Provider Configuration

## Status
Accepted

## Context
Users should be able to connect their own LLM provider, similar to connecting social media accounts (Facebook, Instagram). The system needs flexibility to switch between Anthropic, OpenAI, Gemini, Ollama, and other providers without code changes.

## Decision
Use the `laravel/ai` SDK (multi-provider via Prism) as the abstraction layer. Implement hierarchical configuration resolution:

1. **User config** — per-user provider/model preferences and API keys.
2. **Global config** — system-wide defaults set by administrators.
3. **.env fallback** — environment-level defaults for development and initial setup.

An `AiConfiguration` Eloquent model stores encrypted API keys per user/scope. The `AiConfigResolver` service handles the resolution chain, returning the first available configuration. All provider interactions go through `laravel/ai`, ensuring provider-agnostic code in the domain layer.

## Consequences
### Positive
- Provider-agnostic application code — switching providers requires no code changes.
- Users can bring their own API keys, distributing cost and enabling model preference.
- Easy to add new providers as `laravel/ai` expands its adapter ecosystem.
- Hierarchical resolution provides sensible defaults while allowing customization.

### Negative
- Different providers may return varying quality results for the same prompt, leading to inconsistent user experience.
- Need to handle provider-specific quirks (rate limits, token counts, response formats) behind the abstraction.
- Encrypted key storage adds complexity to the configuration management layer.

### Risks
- User-provided API keys may have insufficient quotas or permissions, requiring graceful degradation.
- Provider API changes may break functionality until `laravel/ai` releases adapter updates.
