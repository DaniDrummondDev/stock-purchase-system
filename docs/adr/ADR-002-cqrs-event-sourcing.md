# ADR-002: CQRS and Event Sourcing

## Status
Accepted

## Context
The system needs to separate read and write concerns for financial operations. The PurchaseEngine bounded context requires a complete audit trail of all state changes to support regulatory compliance and debugging of purchase consolidation logic.

## Decision
Apply CQRS (Commands + Queries + Handlers) across all bounded contexts to separate read and write models. Event Sourcing is applied exclusively to the `PurchaseEngine/Aggregates/CompraConsolidada` aggregate via `spatie/laravel-event-sourcing`. All other bounded contexts use standard Eloquent persistence with `owen-it/laravel-auditing` for audit trail.

- **Commands** represent intent to change state (e.g., `ExecutarCompraCommand`).
- **Queries** represent intent to read state (e.g., `ConsultarCustodiaQuery`).
- **Handlers** process commands/queries and return results.
- **Event Sourced aggregate** (`CompraConsolidada`) stores every domain event, enabling full replay and temporal queries.

## Consequences
### Positive
- Clear separation of concerns between reads and writes across the entire application.
- Full history of all state changes for purchase operations, enabling audit, replay, and debugging.
- Audit trail on all Eloquent models via `owen-it/laravel-auditing` without the complexity of full ES.
- Event Sourcing on PurchaseEngine enables temporal queries (e.g., portfolio state at any point in time).

### Negative
- Event Sourcing adds significant complexity to the PurchaseEngine bounded context.
- Two persistence patterns to maintain (ES for PurchaseEngine, Eloquent for everything else).
- Developers must understand both patterns and know when each applies.

### Risks
- Event stream growth over time may require snapshotting strategy for CompraConsolidada.
- Projector/reactor failures can cause read model inconsistency if not properly monitored.
