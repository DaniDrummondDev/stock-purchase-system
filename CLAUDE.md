# Stock Purchase System (Compra Programada de Ações)

## Project Overview
Automated stock purchase system adapted from Itaú Corretora technical challenge. Clients subscribe to a recurring investment plan in a recommended portfolio of 5 stocks (Top Five).

## Tech Stack
- **Backend:** PHP 8.3 + Laravel 12
- **Database:** PostgreSQL 16 + pgvector
- **Queue/Cache:** Redis 7
- **Messaging:** Apache Kafka (Confluent)
- **Frontend:** Livewire
- **Testing:** Pest PHP
- **AI:** `laravel/ai` SDK (multi-provider: Anthropic, OpenAI, Gemini, Ollama, Voyage AI, etc.)

## Architecture
Monolito Modular DDD with CQRS and Event Sourcing (PurchaseEngine only).

### Bounded Contexts
- `Domain/Client` — Gestão de clientes, conta gráfica, custódia
- `Domain/Basket` — Cesta Top Five
- `Domain/PurchaseEngine` — Motor de compra programada (Event Sourced)
- `Domain/Rebalancing` — Rebalanceamento tipo A e B
- `Domain/MarketData` — Parser COTAHIST, cotações B3
- `Domain/Tax` — IR Dedo-Duro e IR Vendas
- `Domain/AI` — Recomendação, análise de risco, assistente virtual

### Layer Organization
- `app/Domain/` — Pure domain logic, no framework dependencies
- `app/Application/` — Commands, Queries, Handlers (CQRS)
- `app/Infrastructure/` — Eloquent models, Kafka producers, AI clients
- `app/Presentation/` — Controllers, Livewire components, views

## Conventions
- **Language:** Code in English, domain terms in Portuguese (ticker, cesta, cliente, custódia)
- **IDs:** UUID for all primary keys
- **Money:** `decimal(12,2)` — never use float for financial values
- **Tests:** Pest PHP, minimum 70% coverage
- **Event Sourcing:** Only on `PurchaseEngine/Aggregates/CompraConsolidada`
- **Audit:** All Eloquent models use `owen-it/laravel-auditing`

## Key Commands
```bash
# Docker
docker-compose up -d

# Tests
php artisan test
php artisan test --coverage --min=70

# Migrations
php artisan migrate

# Swagger
php artisan l5-swagger:generate
```

## Important Files
- `docs/superpowers/specs/2026-03-15-stock-purchase-system-design.md` — Full design spec
- `docs/teste_itau_v2-main/` — Original challenge specification
- `config/kafka.php` — Kafka configuration
- `config/event-sourcing.php` — Event Sourcing configuration
