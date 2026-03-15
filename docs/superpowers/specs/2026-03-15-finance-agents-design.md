# Finance Agents System — Design Spec

> **Date:** 2026-03-15
> **Status:** Draft
> **Context:** Stock Purchase System (Compra Programada de Ações)

## 1. Overview

Sistema de agentes financeiros especializados que operam em paralelo, integrados com LLMs via `laravel/ai`, para fornecer análise de carteira, avaliação de risco, inteligência de mercado, simulações, educação financeira e orientação fiscal aos utilizadores do sistema de compra programada de ações.

### Decisões-chave

- **6 agentes especializados** — Portfolio Analyst, Risk Analyst, Tax/IR Analyst, Market Intelligence, Simulator, Educator
- **Orquestração híbrida (Maestro)** — LLM decide o plano de execução via tool calling; Laravel executa via Jobs/Queues
- **Data providers plugáveis** — Interface única, começa com COTAHIST (interno) + BCB API (externo)
- **Triggers scheduled + event-driven** — Configuráveis pelo utilizador
- **Notificações multicanal configuráveis** — In-app (sempre), email, chat proativo (opt-in)
- **Zero acoplamento** — Agentes comunicam com outros BCs via CQRS queries e domain events

---

## 2. Architecture

### 2.1 High-Level Diagram

```
┌─────────────────────────────────────────────────────┐
│                   TRIGGERS                           │
│  Scheduled Jobs │ Domain Events │ User Chat Request  │
└────────┬────────────────┬──────────────┬────────────┘
         │                │              │
         ▼                ▼              ▼
┌─────────────────────────────────────────────────────┐
│              MAESTRO (LLM Orchestrator)              │
│  Recebe contexto → LLM decide plano via tool calling │
│  → Despacha agentes → Consolida resultado            │
└────────┬────────────────────────────────────────────┘
         │ despacha em paralelo/sequencial
         ▼
┌─────────────────────────────────────────────────────┐
│            AGENTES ESPECIALIZADOS (Tools)            │
│  ┌──────────┐ ┌──────────┐ ┌───────────┐           │
│  │Portfolio │ │  Risk    │ │  Tax/IR   │           │
│  │ Analyst  │ │ Analyst  │ │ Analyst   │           │
│  ├──────────┤ ├──────────┤ ├───────────┤           │
│  │ Market   │ │Simulator │ │ Educator  │           │
│  │ Intel    │ │          │ │           │           │
│  └──────────┘ └──────────┘ └───────────┘           │
└────────┬────────────────────────────────────────────┘
         │ consulta
         ▼
┌─────────────────────────────────────────────────────┐
│           DATA PROVIDERS (Plugáveis)                 │
│  ┌──────────┐ ┌──────────┐ ┌───────────┐           │
│  │COTAHIST  │ │ BCB API  │ │  Custom   │           │
│  │(interno) │ │(Selic...)│ │ Provider  │           │
│  └──────────┘ └──────────┘ └───────────┘           │
└─────────────────────────────────────────────────────┘
         │ notifica
         ▼
┌─────────────────────────────────────────────────────┐
│        NOTIFICATION CHANNELS (Configuráveis)         │
│  In-App (sempre) │ Email │ Chat Proativo            │
└─────────────────────────────────────────────────────┘
```

### 2.2 Execution Flow

**On-demand (chatbot):**
1. Utilizador faz pergunta no chat
2. Maestro recebe pergunta + contexto do cliente (carteira, status, valor mensal)
3. LLM decide quais tools/agentes chamar e em que ordem via tool calling
4. Agentes executam em paralelo via Laravel Jobs (quando independentes)
5. Resultados retornam ao Maestro como `AgentResult`
6. LLM consolida numa resposta natural e acionável
7. Resposta entregue ao utilizador

**Autonomous (background):**
1. Scheduled Job ou Domain Event dispara trigger
2. Trigger avalia condição (ex: variação > 5%)
3. Se relevante, despacha o Maestro com contexto
4. Mesmo fluxo de execução e consolidação
5. Resultado gera notificação via canais configurados pelo utilizador

### 2.3 Domain/AI Folder Structure

Integra-se com os sub-BCs já definidos no design principal (`Recommendation/`, `RiskAnalysis/`, `Assistant/`). Os agentes vivem nos respectivos sub-BCs, e os componentes partilhados (Orchestrator, Contracts, DataProviders) ficam na raiz do BC.

```
Domain/AI/
├── Contracts/                              # Shared agent contracts
│   ├── FinanceAgentInterface.php
│   ├── AgentContext.php                    # VO — contexto de execução
│   ├── AgentResult.php                     # VO — resultado padronizado
│   ├── DataProviderInterface.php
│   ├── DataQuery.php                       # VO — query para providers
│   └── DataResult.php                      # VO — resultado de providers
├── Orchestrator/                           # Maestro (shared)
│   ├── AgentOrchestrator.php
│   ├── ExecutionPlan.php
│   └── OrchestratorPromptBuilder.php
├── Recommendation/                         # Existing sub-BC (Sprint 8)
│   ├── Entities/
│   ├── Services/
│   │   └── RecommendationService.php
│   ├── Agents/
│   │   └── PortfolioAnalystAgent.php       # Wraps recommendation logic
│   └── ValueObjects/
├── RiskAnalysis/                           # Existing sub-BC (Sprint 9)
│   ├── Entities/
│   ├── Services/
│   │   └── RiskAnalysisService.php
│   ├── Agents/
│   │   └── RiskAnalystAgent.php            # Wraps risk analysis logic
│   └── ValueObjects/
├── Assistant/                              # Existing sub-BC (Sprint 9)
│   ├── Entities/
│   ├── Services/
│   │   └── ChatAssistantService.php
│   ├── Agents/
│   │   ├── EducatorAgent.php               # Educational responses
│   │   └── SimulatorAgent.php              # What-if projections
│   └── ValueObjects/
├── MarketIntelligence/                     # New sub-BC
│   ├── Agents/
│   │   └── MarketIntelligenceAgent.php
│   └── Services/
├── Tax/                                    # Agents for existing Tax BC
│   └── Agents/
│       └── TaxAnalystAgent.php
├── DataProviders/                          # Pluggable data sources
│   ├── DataProviderRegistry.php
│   ├── DataProviderManager.php
│   ├── CotahistProvider.php
│   └── BcbProvider.php
└── Notifications/                          # Agent notification system
    ├── AgentNotificationDispatcher.php
    ├── NotificationPrioritizer.php
    └── ProactiveChatInjector.php
```

**Mapping agents → sub-BCs:**
- `PortfolioAnalystAgent` → `Recommendation/` — evolui o RecommendationService, adiciona análise de carteira
- `RiskAnalystAgent` → `RiskAnalysis/` — evolui o RiskAnalysisService, adiciona score e alertas
- `TaxAnalystAgent` → `Tax/Agents/` — consome dados do BC Tax existente
- `MarketIntelligenceAgent` → `MarketIntelligence/` — novo sub-BC para dados externos
- `SimulatorAgent` → `Assistant/` — projeções what-if como extensão do assistente
- `EducatorAgent` → `Assistant/` — educação contextualizada como extensão do assistente

---

## 3. Specialized Agents

### 3.1 Common Interface

Cada agente implementa `FinanceAgentInterface`:

```php
interface FinanceAgentInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getParameterSchema(): array; // JSON Schema
    public function execute(AgentContext $context): AgentResult;
}
```

### 3.2 AgentContext Value Object

Contexto de execução passado a cada agente:

```php
class AgentContext
{
    public function __construct(
        public readonly string $clienteId,
        public readonly string $request,           // Pergunta ou descrição do trigger
        public readonly string $triggerType,        // 'chat', 'scheduled', 'event'
        public readonly ?string $chatSessionId,     // Null para triggers background
        public readonly array $additionalParams,    // Parâmetros extra (cenário simulação, etc.)
    ) {}
}
```

### 3.3 AgentResult Value Object

Retorno padronizado de todos os agentes:

```php
class AgentResult
{
    public function __construct(
        public readonly array $data,         // Dados estruturados
        public readonly string $summary,     // Texto resumido para consolidação
        public readonly float $confidence,   // 0.0 a 1.0
        public readonly array $metadata,     // Tempo execução, fontes usadas
    ) {}
}
```

### 3.4 DataQuery / DataResult Value Objects

```php
class DataQuery
{
    public function __construct(
        public readonly string $capability,      // 'quotations', 'macro_indicators', etc.
        public readonly array $params,           // Ex: ['ticker' => 'PETR4', 'date' => '2026-03-15']
        public readonly ?int $cacheTtlSeconds,   // Override do TTL default (null = default)
    ) {}
}

class DataResult
{
    public function __construct(
        public readonly array $data,
        public readonly string $providerName,    // Provider que respondeu
        public readonly bool $fromCache,         // Se veio do cache
        public readonly \DateTimeImmutable $fetchedAt,
    ) {}
}
```

### 3.5 Agent Descriptions

**Portfolio Analyst** — Analisa a carteira do cliente. Calcula composição real vs cesta ideal, desvios percentuais, P/L por ativo e total, rentabilidade acumulada, preço médio vs cotação atual. É o agente mais chamado — quase toda pergunta sobre "minha carteira" passa por ele.

**Risk Analyst** — Avalia risco da carteira. Concentração por ativo (índice Herfindahl), volatilidade histórica (desvio padrão dos retornos via COTAHIST), correlação entre ativos, exposição setorial. Gera score 0.0-1.0 (alinhado com `analise_risco_cache.score_risco`), classifica em faixas: conservador (0.0-0.3), moderado (0.3-0.6), agressivo (0.6-0.8), crítico (0.8-1.0). Alertas Kafka publicados quando score > 0.7 (alinhado com o design principal — tópico `alertas-risco`). Produz alertas específicos. Evolui o `RiskAnalysisService` planeado no Sprint 9.

**Tax/IR Analyst** — Especialista em imposto de renda sobre ações. Calcula IR dedo-duro acumulado, simula IR sobre vendas hipotéticas, verifica se o cliente está perto do limite de R$20k/mês de vendas, e alerta sobre obrigações fiscais. Consulta a tabela `operacoes_ir` e eventos Kafka de IR.

**Market Intelligence** — Busca dados externos via Data Providers. Cotações recentes, indicadores macro (Selic, IPCA, câmbio via BCB), e opcionalmente notícias. Cruza com a carteira do cliente para contextualizar (ex: "Selic subiu 0.5pp, isso tende a impactar negativamente MGLU3 que está na sua carteira").

**Simulator** — Faz projeções "what-if". Recebe cenários do utilizador (alterar aporte, trocar ativo, mudar percentuais) e simula o impacto a 3/6/12 meses usando dados históricos e a mecânica real do sistema (compra programada nos dias 5/15/25, lote padrão/fracionário, PM). Usa retornos históricos como base — deixa explícito que são projeções, não previsões.

**Educator** — Explica conceitos financeiros no contexto da carteira do cliente. Em vez de respostas genéricas, usa dados reais: "Preço Médio é o custo médio das suas compras. O seu PM de PETR4 é R$28,50 porque você comprou 10 ações a R$30 e depois 10 a R$27". Conhece todas as regras de negócio do sistema (RN-001 a RN-070) e o glossário.

---

## 4. Maestro — LLM Orchestrator

### 4.1 Execution Flow

1. Recebe pedido (pergunta do chat, trigger de evento, ou job scheduled)
2. Monta contexto: dados básicos do cliente, histórico recente de chat, lista de tools/agentes com descrições
3. Envia para LLM via `laravel/ai` com system prompt: "Você é um orquestrador financeiro. Analise o pedido e decida quais ferramentas usar e em que ordem. Se ferramentas não têm dependência entre si, marque-as como paralelas."
4. LLM responde com tool calls — ex: `[portfolio_analyst(clienteId), risk_analyst(clienteId)]` paralelo, depois `[simulator(clienteId, cenario)]` sequencial
5. Maestro despacha Jobs paralelos, aguarda, despacha sequenciais
6. Coleta `AgentResult`s, envia à LLM para consolidação
7. Retorna resposta final

### 4.2 Safety Controls

| Controle | Valor padrão | Descrição |
|----------|-------------|-----------|
| Timeout por agente | 30s | Configurável. Maestro prossegue sem resultado se exceder |
| Budget tokens (planning) | 4000 tokens | Chamada de planificação (tool calling) |
| Budget tokens (consolidation) | 8000 tokens | Chamada de consolidação (mais pesada, recebe agent results) |
| Max agentes simultâneos | 4 | Evita sobrecarga de Jobs |
| Fallback plans | Por tipo de trigger | Se LLM falhar, plano default (ex: carteira → Portfolio Analyst) |
| Circuit breaker | 3 falhas consecutivas | Agente desactivado temporariamente |
| Proactive chat max | 3/dia por cliente | Anti-spam: máximo mensagens proativas por utilizador |

### 4.3 Caching

- Resultados de agentes com dados que mudam lentamente são cacheados em Redis com TTL configurável
- **Risk Analyst:** TTL 24h (recalculado no `DailyRiskScan`)
- **Market Intel macro:** TTL 1h para cotações, 6h para indicadores BCB
- **Portfolio Analyst:** TTL invalidado por eventos (`CompraDistribuida`, `RebalanceamentoTipoA`, `RebalanceamentoTipoB`)
- Maestro verifica cache antes de despachar — se válido, usa directo sem chamar o agente

---

## 5. Data Providers

### 5.1 Interface

```php
interface DataProviderInterface
{
    public function getName(): string;
    public function getCapabilities(): array;
    public function isAvailable(): bool;
    public function query(DataQuery $query): DataResult;
}
```

### 5.2 Initial Providers

**CotahistProvider** — Encapsula acesso à tabela `cotacoes` (parser COTAHIST do Sprint 3). Expõe: cotação por ticker/data, histórico de preços, volume, variação percentual. Capabilities: `[quotations, historical_prices, volume]`. Sempre disponível (dados locais).

**BcbProvider** — Consome API pública do Banco Central do Brasil (SGS - Sistema Gerenciador de Séries Temporais). Endpoints gratuitos, sem autenticação. Séries: Selic (432), IPCA (433), câmbio USD/BRL (1). Cache 6h. Capabilities: `[macro_indicators, interest_rates, inflation, exchange_rates]`.

### 5.3 Provider Registry

Providers registados via `DataProviderRegistry` no service container Laravel. Para adicionar novo provider:
1. Criar classe implementando `DataProviderInterface`
2. Registar no `DataProviderRegistry` via service provider
3. Provider fica automaticamente disponível para todos os agentes

### 5.4 Data Provider Manager

Agentes não chamam providers diretamente. Usam `DataProviderManager` que recebe query descritiva e roteia para o provider com a capability adequada. Se múltiplos providers oferecem o mesmo dado, usa prioridade configurável (dados internos primeiro, API externa como fallback).

### 5.5 Rate Limiting and Resilience

Providers externos têm protecções adicionais:
- **Rate limiting:** Configurável por provider via Redis (ex: BcbProvider max 60 requests/hora)
- **Retry com backoff:** 3 tentativas com exponential backoff (1s, 2s, 4s)
- **Circuit breaker:** Se provider falha 5 vezes consecutivas, desactivado por 10 minutos
- **Graceful degradation:** Se provider externo está indisponível, agente opera apenas com dados internos e informa na resposta que dados macro não estão disponíveis

---

## 6. Triggers

### 6.1 Scheduled Jobs (Laravel Scheduler)

| Job | Frequência | Agentes acionados | Output |
|-----|-----------|-------------------|--------|
| `DailyRiskScan` | Diário 20h (após pregão) | Risk Analyst + Portfolio Analyst | Notificação se score mudou de faixa |
| `MarketBriefing` | Diário 9h (pré-pregão) | Market Intelligence | Resumo macro + impacto nas carteiras |
| `WeeklyPortfolioReport` | Sexta 18h | Portfolio + Risk + Tax | Relatório semanal completo |
| `RebalancingCheck` | Diário 20:30h | Portfolio Analyst | Alerta se desvio > 5pp (RN-037) |
| `TaxAlertMonthly` | Dia 1 de cada mês | Tax/IR Analyst | Resumo IR do mês anterior |

### 6.2 Event-Driven (Domain Events)

Eventos alinhados com os domain events definidos no design principal:

| Evento | BC Origem | Agentes acionados | Output |
|--------|-----------|-------------------|--------|
| `CompraDistribuida` | PurchaseEngine | Portfolio + Tax | Atualiza análise e verifica IR dedo-duro |
| `CotacoesImportadas` (novo) | MarketData | Market Intel + Risk | Alerta de volatilidade (se variação > 5%) |
| `RebalanceamentoTipoA` | Rebalancing | Portfolio + Risk | Recalcula score e composição |
| `RebalanceamentoTipoB` | Rebalancing | Portfolio + Risk | Recalcula score e composição |
| `ClienteAderiu` | Client | Educator | Mensagem de boas-vindas com educação básica |
| `ValorMensalAlterado` | Client | Simulator + Portfolio | Simula impacto da mudança no aporte |
| `IRDedoDuroCalculado` | Tax | Tax Analyst | Notifica cliente sobre IR retido |

**Nota:** `CotacoesImportadas` é um novo evento a ser criado. Será disparado pela infrastructure layer (job de importação COTAHIST) e não pelo domain layer do BC MarketData, já que o evento é um side-effect de um processo de importação. O BC MarketData precisa de uma pasta `Events/` adicionada à sua estrutura. Os demais eventos já estão definidos no design principal.

**Nota 2:** Após a migração de `chat_messages`, a tabela `chat_contextos` mantém: `id`, `cliente_id`, `embedding` (vector), `created_at`, `updated_at`. Funciona como sessão de chat com embedding para RAG, enquanto `chat_messages` armazena o histórico normalizado.

### 6.3 User Configuration

O utilizador configura no painel de alertas:
- **Triggers activos** — toggle on/off por job/evento
- **Canais por tipo** — crítico (in-app + email), informativo (in-app), educacional (chat proativo — opcional)
- **Horários preferenciais** — override do horário default (ex: briefing às 7h em vez de 9h)
- **Limiar de alertas** — configurável (ex: variação > 3% em vez de 5%)

---

## 7. Notification System

### 7.1 Channels

**In-App (sempre activo)** — Notificações na tabela `notifications` (Laravel). Badge no dashboard + feed com histórico. Campos: tipo (alerta/info/educacional), prioridade (crítica/normal/baixa), título, corpo, dados estruturados, flag lido/não lido.

**Email** — Laravel Mail com templates Blade. Apenas para notificações críticas ou que excedam limiar configurado. Inclui resumo + link para detalhes. Frequência máxima configurável (ex: max 1 email/hora).

**Chat Proativo (opt-in, off por default)** — Quando um trigger gera insight relevante, injeta mensagem no chat do assistente: "Olá João, notei que VALE3 caiu 6% hoje. A sua exposição neste ativo é 25% da carteira. Quer que eu analise o impacto e sugira rebalanceamento?" Utilizador pode responder naturalmente.

### 7.2 Priority Classification

| Prioridade | Critérios | Canais |
|-----------|-----------|--------|
| Crítica | Score risco → "crítico", variação > 10%, IR a pagar | Todos os canais activos |
| Normal | Relatórios periódicos, rebalanceamento sugerido, compra executada | In-app + email se configurado |
| Baixa | Dicas educacionais, market briefing informativo | Apenas in-app |

`NotificationPrioritizer` avalia critérios automaticamente. Canal final = prioridade × preferências do utilizador.

---

## 8. Data Model — New Tables

### 8.1 alert_preferences

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid | PK |
| cliente_id | uuid | FK → clientes |
| trigger_type | varchar(100) | Nome do job ou evento |
| enabled | boolean | Activo/inactivo, default true |
| channels | jsonb | `["in_app", "email"]` |
| threshold_config | jsonb | Configurações de limiar (ex: `{"variacao_min": 3}`) |
| schedule_override | varchar(50) | Cron override opcional |
| created_at | timestamp | — |
| updated_at | timestamp | — |

Unique index: `(cliente_id, trigger_type)`

### 8.2 agent_executions

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid | PK |
| cliente_id | uuid | FK → clientes (nullable para jobs globais) |
| agent_name | varchar(50) | Nome do agente |
| trigger_type | varchar(20) | 'scheduled', 'event', 'chat' |
| trigger_reference | varchar(100) | Nome do job/evento/session |
| input_context | jsonb | Parâmetros enviados ao agente |
| result_data | jsonb | Dados retornados |
| result_summary | text | Resumo textual |
| confidence | decimal(3,2) | 0.00 a 1.00 |
| execution_time_ms | integer | Tempo de execução em ms |
| status | varchar(20) | 'processing', 'success', 'timeout', 'error' |
| error_message | text | Nullable — detalhes se falhou |
| created_at | timestamp | — |
| updated_at | timestamp | Quando status mudou (processing → success/error) |

Indexes: `(cliente_id, agent_name, created_at)`, `(status, created_at)`

### 8.3 data_provider_configs

Configuração global de data providers (administrador). Utilizadores não configuram providers — apenas administradores decidem quais providers estão activos e com que prioridade.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid | PK |
| provider_name | varchar(50) | Nome do provider (unique) |
| enabled | boolean | Default true |
| settings | jsonb | Config específica do provider |
| api_key | text | Encrypted — se provider requer auth |
| priority | integer | Ordem de preferência (menor = maior prioridade) |
| rate_limit | integer | Max requests/hora (nullable = sem limite) |
| created_at | timestamp | — |
| updated_at | timestamp | — |

Unique index: `(provider_name)`

### 8.4 chat_messages

Substitui a coluna `mensagens` (jsonb) da tabela `chat_contextos` existente. A normalização permite queries por role, paginação, e associação de agent_results por mensagem. A migração remove a coluna `chat_contextos.mensagens` e cria esta tabela.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid | PK |
| cliente_id | uuid | FK → clientes |
| session_id | uuid | FK → chat_contextos |
| role | varchar(20) | 'user', 'assistant', 'system', 'proactive' |
| content | text | Conteúdo da mensagem |
| agent_results | jsonb | AgentResults usados para gerar a resposta (nullable) |
| tokens_used | integer | Total de tokens consumidos |
| created_at | timestamp | — |

Mensagens são imutáveis — uma vez criadas, não são editadas. Sem coluna `updated_at` por design.

Indexes: `(session_id, created_at)`, `(cliente_id, role, created_at)`

**Migration plan:** A migração cria `chat_messages`, migra dados existentes de `chat_contextos.mensagens` (JSONB → linhas normalizadas), e depois remove a coluna `mensagens` de `chat_contextos`.

---

## 9. Integration with Existing Architecture

### 9.1 Cross-BC Communication

- **Leitura (CQRS queries):** Agentes usam queries existentes — `ObterCarteiraQuery`, `ObterRentabilidadeQuery`, `ObterCestaAtualQuery`, `ObterCustodiaMasterQuery`, `ObterAnaliseRiscoQuery`. Para cotações, usam o `CotahistProvider` directamente (não via CQRS)
- **Eventos (Laravel Events):** Consomem domain events: `CompraDistribuida`, `RebalanceamentoTipoA/B`, `ClienteAderiu`, `ValorMensalAlterado`, `IRDedoDuroCalculado`, `CotacoesImportadas` (novo)
- **Kafka:** Consomem tópicos existentes (ir-dedo-duro, compra-executada). Novos tópicos: `agent-results` (audit trail), `proactive-alerts`

### 9.2 laravel/ai Integration

- `AgentOrchestrator` usa `laravel/ai` para planificação (tool calling) e consolidação (text generation)
- Cada agente registado como tool no formato JSON Schema esperado pelo `laravel/ai`
- Respeita `AiConfigResolver` hierárquico (user config → global → .env)
- Agentes que precisam de LLM internamente (Educator, Market Intel) também usam `laravel/ai` via resolver

### 9.3 Infrastructure Services

- **Redis:** Cache de `AgentResult`, rate limiting de chamadas LLM, locks para evitar execuções duplicadas
- **Kafka:** Triggers event-driven, novos tópicos `agent-results` e `proactive-alerts`
- **PostgreSQL + pgvector:** Educator e Market Intel usam embeddings para RAG (busca semântica no glossário, regras de negócio, histórico)

---

## 10. API Endpoints

### 10.1 Chat / Agents

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/ai/chat` | Envia mensagem ao assistente (invoca Maestro) |
| GET | `/api/ai/chat/sessions` | Lista sessões de chat do cliente |
| GET | `/api/ai/chat/sessions/{id}/messages` | Histórico de mensagens de uma sessão |

### 10.2 Alert Preferences

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/ai/alert-preferences` | Lista preferências de alerta do cliente |
| PUT | `/api/ai/alert-preferences/{trigger_type}` | Atualiza preferência (enable/disable, canais, limiar) |
| PUT | `/api/ai/alert-preferences/bulk` | Atualiza múltiplas preferências de uma vez |

### 10.3 Agent Execution History

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/ai/agent-executions` | Lista execuções recentes (paginado, filtros por agente/status) |
| GET | `/api/ai/agent-executions/{id}` | Detalhe de uma execução específica |

### 10.4 Notifications

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/notifications` | Lista notificações do cliente (paginado) |
| PUT | `/api/notifications/{id}/read` | Marca notificação como lida |
| PUT | `/api/notifications/read-all` | Marca todas como lidas |
| GET | `/api/notifications/unread-count` | Contagem de não lidas (para badge) |

### 10.5 Livewire Components

| Componente | Descrição |
|-----------|-----------|
| `AI/ChatWindow` | Chat do assistente virtual com suporte a proactive messages |
| `AI/AlertPreferences` | Painel de configuração de alertas |
| `AI/AgentHistory` | Histórico de execuções de agentes |
| `Notifications/NotificationBell` | Badge + dropdown de notificações |
| `Notifications/NotificationFeed` | Feed completo de notificações |

---

## 11. Testing Strategy

### 11.1 Unit Tests

- Cada agente testado isoladamente com data providers mockados
- `AgentOrchestrator` testado com LLM mockada (respostas tool calling predefinidas)
- `NotificationPrioritizer` testado com diferentes combinações de prioridade
- `DataProviderManager` testado com routing e fallback entre providers
- Value Objects (`AgentContext`, `AgentResult`, `DataQuery`, `DataResult`)

### 11.2 Integration Tests

- Fluxo completo Maestro → Agentes → Consolidação (com LLM real, limitado a CI com API key)
- `BcbProvider` contra API real (com VCR/recorded responses para CI)
- `CotahistProvider` contra base de teste com dados COTAHIST importados
- Triggers event-driven: emitir evento → verificar agente executou → notificação criada

### 11.3 Feature Tests

- API endpoints (chat, alert preferences, notifications) com autenticação
- Livewire components rendering e interação

---

## 12. Sprint Mapping

O sistema de agentes integra-se no roadmap existente como extensões dos sprints de IA (8 e 9), mais um sprint adicional:

| Sprint | Componente | Dependências |
|--------|-----------|-------------|
| Sprint 8a (AI Foundation + Agents Infra) | Contracts (`FinanceAgentInterface`, `AgentContext`, `AgentResult`), `AgentOrchestrator`, `DataProviderInterface`, `DataProviderManager`, `CotahistProvider`, migrations (agent_executions, data_provider_configs) | Sprints 1-7 |
| Sprint 8b (Recommendation + Portfolio Agent) | `RecommendationService` + `PortfolioAnalystAgent`, EmbeddingService, endpoint POST /api/ai/recomendacao-cesta, endpoint POST /api/ai/chat (básico) | Sprint 8a + Sprint 3 (COTAHIST) |
| Sprint 9a (Risk + Tax Agents) | `RiskAnalysisService` + `RiskAnalystAgent`, `TaxAnalystAgent`, `BcbProvider`, `MarketIntelligenceAgent`, `analise_risco_cache` | Sprint 8b |
| Sprint 9b (Assistant + Simulator + Educator) | `ChatAssistantService` + `SimulatorAgent` + `EducatorAgent`, ChatWindow Livewire, chat_messages migration | Sprint 9a |
| Sprint 10 (Notifications + Triggers) | Alert preferences, `NotificationPrioritizer`, `AgentNotificationDispatcher`, `ProactiveChatInjector`, Scheduled jobs, Event-driven triggers, AlertPreferences Livewire | Sprint 9b |
| Sprint 11 (Finalização) | Coverage ≥ 70%, Swagger completo, ADRs, README, observabilidade | Sprint 10 |

**Nota:** Os sprints 8a/8b e 9a/9b são sub-divisões dos sprints 8 e 9 do roadmap principal. O Sprint 10 (Finalização) do roadmap original passa a Sprint 11 para acomodar o sprint de Notifications + Triggers.
