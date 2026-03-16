# ADR-001: Modular DDD Architecture

## Status
Accepted

## Context
The Itau challenge was originally designed for .NET/C#, adapted to PHP/Laravel. The system requires organizing 7 bounded contexts (Client, Basket, PurchaseEngine, Rebalancing, MarketData, Tax, AI) in a maintainable structure that enforces clear boundaries while keeping deployment simple.

## Decision
Adopt a Monolito Modular DDD with 4 layers:

- **Domain/** — Pure domain logic, no framework dependencies. Each BC is a folder under `app/Domain/` containing Entities, Value Objects, Domain Events, and Repository interfaces.
- **Application/** — Commands, Queries, and Handlers following the CQRS pattern. Orchestrates domain logic without containing business rules.
- **Infrastructure/** — Eloquent models, Kafka producers/consumers, AI clients, external service adapters. Implements repository interfaces defined in Domain.
- **Presentation/** — Controllers, Livewire components, views. Thin layer that delegates to Application handlers.

Each bounded context (Client, Basket, PurchaseEngine, Rebalancing, MarketData, Tax, AI) lives as a separate folder under `Domain/` with its own internal structure.

## Consequences
### Positive
- Single deployment unit simplifies DevOps and reduces infrastructure cost.
- Shared PostgreSQL database enables transactional consistency across contexts.
- Easy refactoring — boundaries can be tightened or relaxed without changing deployment topology.
- Clear layer separation prevents coupling between domain logic and framework concerns.

### Negative
- Risk of layer violation if developers are not disciplined (e.g., importing Eloquent in Domain layer).
- All contexts share the same process, so a failure in one context can impact others.

### Risks
- As the codebase grows, the monolith may become harder to scale horizontally for individual contexts.
- Without automated architecture tests (e.g., deptrac), layer violations may go undetected.
