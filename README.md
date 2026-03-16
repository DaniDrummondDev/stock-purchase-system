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

# 7. Execute as migrations
docker compose exec app php artisan migrate

# 8. Crie os utilizadores de teste
docker compose exec app php artisan db:seed

# 9. Pronto!
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

### Exemplos

**Adesão:**
```bash
curl -X POST http://localhost:8000/api/clientes/adesao \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "João Silva",
    "cpf": "12345678909",
    "email": "joao@email.com",
    "valorMensal": 1000.00
  }'
```

**Consultar carteira:**
```bash
curl http://localhost:8000/api/clientes/{clienteId}/carteira
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

## Licença

MIT
