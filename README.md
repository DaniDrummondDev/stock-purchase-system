# Compra Programada de Ações Itaú

[![CI](https://github.com/DaniDrummondDev/stock-purchase-system/actions/workflows/ci.yml/badge.svg)](https://github.com/DaniDrummondDev/stock-purchase-system/actions/workflows/ci.yml)

## Motivação

Este projeto nasceu de um desafio técnico da **Itaú Corretora** para desenvolver um sistema de compra programada de ações. O desafio original foi pensado para .NET/C#, mas decidi adaptá-lo para o stack com que trabalho no dia a dia — **PHP/Laravel** — mantendo todas as regras de negócio do desafio original e indo além: adicionei integração com IA (recomendação de cesta, análise de risco, assistente virtual), agentes financeiros especializados, e uma camada de segurança baseada no OWASP Top 10 2025.

O objectivo não é apenas resolver o desafio, mas construir um sistema completo que demonstre boas práticas de arquitectura (DDD, CQRS, Event Sourcing), infraestrutura moderna (Docker, Kafka, pgvector), e integração inteligente com LLMs.

## Tech Stack

| Componente | Tecnologia |
|-----------|------------|
| Backend | PHP 8.3 + Laravel 12 |
| Database | PostgreSQL 16 + pgvector |
| Cache/Queue | Redis 7 |
| Messaging | Apache Kafka (Confluent) |
| Frontend | Livewire |
| Testing | Pest PHP |
| AI | laravel/ai SDK (Anthropic, OpenAI, Gemini, Voyage AI) |
| CI | GitHub Actions |

## Arquitectura

Monolito Modular DDD com CQRS e Event Sourcing (PurchaseEngine).

```
app/
├── Domain/           # Lógica de domínio pura, sem dependências do framework
│   ├── Client/       # Gestão de clientes, conta gráfica, custódia
│   ├── Basket/       # Cesta Top Five
│   ├── PurchaseEngine/  # Motor de compra programada (Event Sourced)
│   ├── Rebalancing/  # Rebalanceamento tipo A e B
│   ├── MarketData/   # Parser COTAHIST, cotações B3
│   ├── Tax/          # IR Dedo-Duro e IR Vendas
│   └── AI/           # Recomendação, análise de risco, assistente virtual
├── Application/      # Commands, Queries, Handlers (CQRS)
├── Infrastructure/   # Eloquent, Kafka, AI clients
└── Presentation/     # Controllers, Livewire, Views
```

## Setup

### Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) e Docker Compose
- [Git](https://git-scm.com/)

### Instalação

```bash
# 1. Clone o repositório
git clone git@github.com:DaniDrummondDev/stock-purchase-system.git
cd stock-purchase-system

# 2. Copie o ficheiro de ambiente
cp .env.example .env

# 3. Configure as variáveis de ambiente
#    Edite .env e preencha:
#    - DB_PASSWORD=sps_secret
#    - DB_HOST=postgres
#    - REDIS_HOST=redis
#    - KAFKA_BROKERS=kafka:29092
#    (ou simplesmente use os valores do Docker Compose)

# 4. Suba os containers
docker compose up -d

# 5. Instale as dependências
docker compose exec app composer install

# 6. Gere a application key
docker compose exec app php artisan key:generate

# 7. Execute as migrations e seed (cria utilizadores de teste)
docker compose exec app php artisan migrate --seed

# 8. Pronto!
```

### Utilizadores de Teste

O seed cria 6 utilizadores prontos para uso (password: `Sps@2026#Secure`):

| Email | Role | Descrição |
|-------|------|-----------|
| `admin@sps.local` | admin | Gestão total do sistema |
| `analyst@sps.local` | analyst | Visualiza dados, gere cestas |
| `auditor@sps.local` | auditor | Apenas leitura + logs |
| `joao@sps.local` | client | Cliente de teste |
| `maria@sps.local` | client | Cliente de teste |
| `carlos@sps.local` | client | Cliente de teste |

O seeder é idempotente — pode ser executado múltiplas vezes sem duplicar dados.

### Acessos

| Serviço | URL |
|---------|-----|
| Aplicação | http://localhost:8000 |
| Kafka UI | http://localhost:8080 |
| PostgreSQL | localhost:5432 (user: `sps_user`) |
| Redis | localhost:6379 |

## Rotas da API

### Clientes

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/clientes/adesao` | Adesão de novo cliente ao programa |
| `POST` | `/api/clientes/{clienteId}/saida` | Saída do cliente do programa |
| `PUT` | `/api/clientes/{clienteId}/valor-mensal` | Alterar valor mensal de investimento |
| `GET` | `/api/clientes/{clienteId}/carteira` | Consultar carteira do cliente |

### Cesta Top Five (Admin)

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/admin/cesta/` | Criar/atualizar cesta |
| `GET` | `/api/admin/cesta/atual` | Consultar cesta ativa |
| `GET` | `/api/admin/cesta/historico` | Histórico de cestas |

### Cotações

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/admin/cotacoes/importar` | Importar arquivo COTAHIST |
| `GET` | `/api/cotacoes/{ticker}/{data}` | Cotação por ticker e data |
| `GET` | `/api/cotacoes/{ticker}` | Última cotação do ticker |

### Motor de Compra (Admin)

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/admin/motor/executar-compra` | Executar compra programada |
| `GET` | `/api/admin/motor/compras` | Listar compras |
| `GET` | `/api/admin/motor/compras/{id}` | Detalhe de uma compra |

### Rebalanceamento (Admin)

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/admin/rebalanceamento/executar` | Executar rebalanceamento |

### AI

| Método | Rota | Descrição |
|--------|------|-----------|
| `POST` | `/api/ai/recomendacao-cesta` | Recomendação IA para cesta |
| `POST` | `/api/ai/chat` | Chat com assistente financeiro |

### Swagger/OpenAPI

Documentação interactiva disponível em: `http://localhost:8000/api/documentation`

```bash
# Gerar documentação Swagger
docker compose exec app php artisan l5-swagger:generate
```

## Testes

```bash
# Todos os testes
docker compose exec app vendor/bin/pest

# Apenas unit tests
docker compose exec app vendor/bin/pest --testsuite=Unit

# Apenas feature tests
docker compose exec app vendor/bin/pest --testsuite=Feature

# Com coverage
docker compose exec app vendor/bin/pest --coverage

# Lint (code style)
docker compose exec app vendor/bin/pint --test
```

## Documentação

| Documento | Descrição |
|-----------|-----------|
| [Roadmap](docs/ROADMAP.md) | Progresso por sprint com checklist |
| [System Design](docs/superpowers/specs/2026-03-15-stock-purchase-system-design.md) | Arquitectura, data model, regras de negócio |
| [Finance Agents](docs/superpowers/specs/2026-03-15-finance-agents-design.md) | Agentes IA especializados em finanças |
| [Security](docs/superpowers/specs/2026-03-15-security-design.md) | OWASP Top 10 2025, auth, RBAC, rate limiting |
| [Regras de Negócio](docs/teste_itau_v2-main/regras-negocio-detalhadas.md) | RN-001 a RN-070 (desafio original) |
| [API Contracts](docs/teste_itau_v2-main/exemplos-contratos-api.md) | Contratos e error codes |
| [ADRs](docs/adr/) | Architecture Decision Records (6 decisões) |
| Swagger UI | `http://localhost:8000/api/documentation` |

## Licença

MIT
