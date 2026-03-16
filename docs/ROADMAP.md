# Roadmap — Stock Purchase System (Compra Programada de Ações)

> Last updated: 2026-03-16

---

## [x] Sprint 0 — Fundação
**Objetivo:** Setup completo do ambiente de desenvolvimento

- [x] 0.1 — Docker Compose (PostgreSQL+pgvector, Kafka, Zookeeper, Redis, Kafka UI)
- [x] 0.2 — Projeto Laravel com estrutura DDD de pastas
- [x] 0.3 — Configuração de packages (Event Sourcing, Auditing, Livewire, Pest)
- [x] 0.4 — Migrations iniciais (17 tabelas) — pgvector conditional for SQLite compat
- [x] 0.5 — CI base (GitHub Actions: lint, tests, security audit)
- [x] 0.6 — CLAUDE.md com convenções do projeto
- [x] 0.7 — Swagger/OpenAPI base
- [x] 0.8 — Configuração AI (laravel/ai, AiConfigResolver, ai_configurations table)

---

## [x] Sprint 1 — Gestão de Clientes (RN-001 a RN-013)
**Objetivo:** CRUD completo de clientes com regras de negócio

- [x] 1.1 — Value Objects (CPF, Email, Money) — CPF with check digits, Money cents-based
- [x] 1.2 — Domain Entities (Cliente, ContaGrafica, Custodia) — PM formula, RN-043 sale behavior
- [x] 1.3 — Domain Events (ClienteAderiu, ClienteSaiu, ValorMensalAlterado)
- [x] 1.4 — Repository Interfaces (ClienteRepository, ContaGraficaRepository, CustodiaRepository)
- [x] 1.5 — Eloquent Models + Repository Implementations — HasUuids, Auditable
- [x] 1.6 — CQRS Commands + Handlers (AderirCliente, SairCliente, AlterarValorMensal)
- [x] 1.7 — CQRS Query + Handler (ObterCarteira)
- [x] 1.8 — API Controller + Routes (4 endpoints) — OpenAPI annotations
- [x] 1.9 — Unit Tests (40 passing) — CPF, Email, Money, Cliente, Custodia
- [x] 1.10 — Feature Tests (API) — 9 tests, 21 assertions, all passing
- [x] 1.11 — Redis extension in Docker — already installed, confirmed working

---

## [x] Sprint 1.5 — Security (OWASP Top 10 2025)
**Objetivo:** Segurança abrangente baseada no OWASP Top 10 2025

### Sprint 1.5a — Auth Foundation
- [x] 1.5a.1 — Argon2id config + password policy (12 chars, mixed case, numbers, symbols)
- [x] 1.5a.2 — Security Headers middleware (X-Frame-Options, X-Content-Type, Referrer-Policy, Permissions-Policy, CSP in prod)
- [x] 1.5a.3 — CORS restritivo (config/cors.php, origins from env)
- [x] 1.5a.4 — Session hardening (encrypt=true, expire_on_close=true)
- [x] 1.5a.5 — Users table UUID migration + cliente_id FK + HasUuids trait
- [x] 1.5a.6 — Existing routes protected with auth middleware (Sprint 7)

### Sprint 1.5b — RBAC
- [x] 1.5b.1 — spatie/laravel-permission: 4 roles (admin, analyst, auditor, client)
- [x] 1.5b.2 — 30+ granular permissions + RolesAndPermissionsSeeder (idempotent)
- [x] 1.5b.3 — Policies per BC (ClientePolicy, CestaPolicy, CompraPolicy)
- [x] 1.5b.4 — TOTP 2FA (pragmarx/google2fa-laravel) — setup, confirm, verify, disable + Enforce2FA middleware
- [x] 1.5b.5 — JWT guard (firebase/php-jwt) — RS256, JwtService + JwtGuard

### Sprint 1.5c — Threat Detection + Logging
- [x] 1.5c.1 — Rate limiting por role (config/security.php + AppServiceProvider)
- [x] 1.5c.2 — IP blacklist table + model
- [x] 1.5c.3 — security_events table + SecurityEventLogger
- [x] 1.5c.4 — Anomaly detection (AnomalyDetector: new IP, off-hours)
- [x] 1.5c.5 — Security alerts (SecurityAlertNotification via mail + database)
- [x] 1.5c.6 — SecurityDashboard Livewire (events, IPs, lockouts)
- [x] 1.5c.7 — Tests (13 new: 2 headers, 4 password policy, 3 RBAC, 4 security events)

---

## [x] Sprint 2 — Cesta Top Five (RN-014 a RN-019)
**Objetivo:** Gestão da cesta de ações recomendada

- [x] 2.1 — Domain Entities (Cesta, CestaAtivo) + Value Objects (Percentual, Ticker)
- [x] 2.2 — Validações: exatamente 5 ativos, soma = 100%, percentual > 0, tickers únicos
- [x] 2.3 — CQRS Commands (CriarCesta, AlterarCesta)
- [x] 2.4 — CQRS Queries (ObterCestaAtual, ObterHistoricoCesta)
- [x] 2.5 — API Endpoints (POST criar/atualizar, GET atual, GET histórico)
- [x] 2.6 — Domain Events (CestaCriada, CestaAlterada com diff)
- [ ] 2.7 — Painel admin básico (Livewire) — deferred to Sprint 7
- [x] 2.8 — Tests (34 new: 8 Percentual, 8 Ticker, 9 Cesta entity, 9 API feature)

---

## [x] Sprint 3 — Market Data / COTAHIST (Parser B3)
**Objetivo:** Parser de arquivos COTAHIST B3 e cache de cotações

- [x] 3.1 — Parser fixed-width (245 chars/linha) — CotahistParserService (pure domain, no framework deps)
- [x] 3.2 — Filtro por tipo de registro (01), BDI (02/96), mercado (010/020)
- [x] 3.3 — Conversão de preços (inteiro com 2 decimais implícitos) — value / 100
- [x] 3.4 — Cache em tabela `cotacoes` — upsert em chunks de 500
- [x] 3.5 — Job assíncrono (fila `cotahist-parser`) — ImportarCotahistJob
- [x] 3.6 — Domain Event: CotacoesImportadas
- [x] 3.7 — Tests (19 new: 4 Cotacao entity, 10 Parser service, 5 API feature)

---

## [x] Sprint 4 — Motor de Compra Programada (RN-020 a RN-044) [Crítico]
**Objetivo:** Implementar o motor de compra consolidada e distribuição

- [x] 4.1 — Datas de execução: 5, 15, 25 (próximo dia útil se fds) — DataExecucaoService
- [x] 4.2 — Consolidação: soma aportes clientes ativos — ConsolidacaoService
- [x] 4.3 — Compra consolidada usando cotação mais recente
- [x] 4.4 — Separação lote padrão (100) vs fracionário (1-99)
- [x] 4.5 — Distribuição proporcional por valor de aporte — DistribuicaoService
- [x] 4.6 — Gestão de resíduos na conta master — CustodiaMaster
- [x] 4.7 — Atualização PM: recalculado em compras (RN-041/042)
- [x] 4.8 — Atualização `valor_total_investido` no cliente
- [x] 4.9 — Registro participantes em `compra_participantes`
- [ ] 4.10 — Event Sourcing: CompraConsolidada aggregate — deferred (events stored, full ES later)
- [x] 4.11 — Tests (22 new: 7 DataExecucao, 4 Consolidacao, 5 Distribuicao, 6 API feature)

---

## [x] Sprint 5 — IR e Kafka (RN-053 a RN-062)
**Objetivo:** Cálculo de impostos e publicação no Kafka

- [x] 5.1 — IR Dedo-Duro: 0.005% sobre valor de cada operação — DedoDuroService
- [x] 5.2 — IR Vendas: 20% sobre lucro líquido (vendas > R$20k/mês) — IRVendaService
- [x] 5.3 — Isenção: vendas ≤ R$20k/mês, prejuízo = IR R$0
- [x] 5.4 — Producer Kafka: KafkaProducer + IRDedoDuroMessage + IRVendaMessage
- [x] 5.5 — Formato mensagens (Tipo 01 e 02) conforme spec
- [x] 5.6 — Tests (13 new: 5 DedoDuro, 6 IRVenda, 2 integration)

---

## [x] Sprint 6 — Rebalanceamento (RN-045 a RN-052)
**Objetivo:** Implementar os dois tipos de rebalanceamento

- [x] 6.1 — Tipo A: mudança composição → vender removidos, comprar novos — RebalanceamentoTipoAService
- [x] 6.2 — Tipo B: desvio > 5pp → rebalancear dentro da cesta — RebalanceamentoTipoBService
- [x] 6.3 — Trigger: CestaAlterada event emitido (listener futuro)
- [x] 6.4 — Análise de desvio com limiar configurável
- [x] 6.5 — Cálculo de IR sobre vendas do rebalanceamento — integra IRVendaService
- [x] 6.6 — Tests (11 new: 3 TipoA, 5 TipoB, 3 API feature)

---

## [x] Sprint 7 — Frontend Livewire (RN-063 a RN-070)
**Objetivo:** Interface completa do sistema

- [x] 7.1 — Dashboard Cliente: saldo total, P/L, rentabilidade %, composição real %
- [x] 7.2 — Tela de Rentabilidade: PM, quantidade, cotação, valor atual (integrado no dashboard)
- [x] 7.3 — Painel Admin: gestão cesta (CRUD), compras programadas, conta master
- [x] 7.4 — Layout base com Tailwind CSS + navegação
- [x] 7.5 — Responsivo (mobile-friendly via Tailwind grid/flex)
- [x] 7.6 — Tests (6 new: 2 dashboard, 4 admin panels)

---

## [x] Sprint 8a — AI Foundation + Agent Infrastructure
**Objetivo:** Base do sistema de agentes financeiros

- [x] 8a.1 — Contracts: FinanceAgentInterface, AgentContext, AgentResult, TriggerType
- [x] 8a.2 — Contracts: DataProviderInterface, DataQuery, DataResult
- [x] 8a.3 — AgentOrchestrator (Maestro) + ExecutionPlan + PromptBuilder + FinanceAgentTool adapter — LLM tool calling via laravel/ai
- [x] 8a.4 — DataProviderRegistry + DataProviderManager (Redis cache, fallback)
- [x] 8a.5 — CotahistProvider (wraps cotacoes table — quotation, historical_prices, volume)
- [x] 8a.6 — Migrations: agent_executions, data_provider_configs + Eloquent models
- [x] 8a.7 — Safety controls: AgentCircuitBreaker (Redis, 3 failures/10min), AgentTimeoutConfig, SafeAgentExecutor
- [x] 8a.8 — Tests (20 new: 3 AgentContext, 6 AgentResult, 6 DataProviderRegistry, 5 CircuitBreaker)

## [x] Sprint 8b — Recommendation + Portfolio Agent
**Objetivo:** Recomendação inteligente de cesta + análise de carteira

- [x] 8b.1 — EmbeddingService (laravel/ai + Voyage AI default, batch in chunks of 20)
- [x] 8b.2 — RecommendationService + pgvector cosine similarity search + LLM rationale
- [x] 8b.3 — PortfolioAnalystAgent (3 actions: analyze_composition, estimate_pl, recommend_basket)
- [x] 8b.4 — Endpoint: POST /api/ai/recomendacao-cesta
- [x] 8b.5 — Endpoint: POST /api/ai/chat (ChatContexto with 50 msg limit)
- [x] 8b.6 — AiCestaRecommendation Livewire (side-by-side current vs AI suggestion, confidence bar, apply button)
- [x] 8b.7 — UpdateAtivoEmbeddingsJob (daily 19h + CotacoesImportadas event listener)
- [x] 8b.8 — Tests (22 new: 3 TickerEmbedding, 4 RecommendationResult, 6 PortfolioAnalyst, 2 Embedding, 4 API, 3 PortfolioAnalysis)
- [x] 8b.9 — Migration: unique + HNSW index on ativo_embeddings + Livewire config fix

---

## [x] Sprint 9a — Risk + Tax + Market Agents
**Objetivo:** Análise de risco, IR e inteligência de mercado

- [x] 9a.1 — RiskAnalysisService (Herfindahl, volatility, concentration) + RiskAnalystAgent (calculate_risk, get_cached_risk)
- [x] 9a.2 — AnaliseRiscoCache Eloquent model (TTL 24h, scopes: forCliente, active, expired)
- [x] 9a.3 — RiscoAlertaMessage + Kafka publish on alertas-risco when score >= 0.7
- [x] 9a.4 — TaxAnalystAgent (analyze_tax_status, simulate_sale_tax) — R$20k threshold, dedo-duro acumulado
- [x] 9a.5 — BcbProvider (Selic 432, IPCA 433, USD/BRL 1) — BCB SGS API, exponential backoff retry
- [x] 9a.6 — MarketIntelligenceAgent (get_market_context) — CotahistProvider + BcbProvider + LLM contextual insights
- [x] 9a.7 — DataProviderManager: rate limiting (Redis sliding window), retry with exponential backoff, provider failure tracking
- [x] 9a.8 — Tests (32 new: 7 RiskBand, 7 RiskScore, 4 RiskAnalysisService, 4 RiskAnalystAgent, 4 TaxAnalyst, 3 MarketIntel, 3 BcbProvider)
- [x] 9a.9 — Domain VOs: RiskScore, RiskBand (enum), PortfolioRiskMetrics, MacroIndicators

## [x] Sprint 9b — Assistant + Simulator + Educator
**Objetivo:** Assistente virtual completo com simulação e educação

- [x] 9b.1 — Enhanced chat endpoint: all 6 agents wired (Portfolio, Risk, Tax, Market, Simulator, Educator)
- [x] 9b.2 — SimulatorAgent (simulate_aporte_change, simulate_ticker_swap, project_portfolio with 3/6/12 month projections)
- [x] 9b.3 — EducatorAgent (explain_concept with 13-term glossary + contextual portfolio examples via LLM)
- [x] 9b.4 — ChatWindow Livewire component (/chat route, message bubbles, typing animation, auto-scroll)
- [x] 9b.5 — chat_messages migration (normalized table: session_id FK, role, content, agent_results jsonb, immutable)
- [x] 9b.6 — ScopeGuardrail (financial keyword matching, blocked content detection for direct buy/sell recommendations)
- [x] 9b.7 — Tests (17 new: 4 Simulator, 5 Educator, 4 ScopeGuardrail, 2 ChatWindow, 2 ChatMessage)

---

## [x] Sprint 10 — Notifications + Triggers
**Objetivo:** Sistema de notificações e triggers automáticos

- [x] 10.1 — alert_preferences migration + AlertPreference model (10 trigger types, channels jsonb, threshold_config)
- [x] 10.2 — NotificationPrioritizer (Critical/Normal/Low enum, score/PL/trigger classification)
- [x] 10.3 — AgentNotificationDispatcher (preference check, channel resolution, Laravel Notification dispatch)
- [x] 10.4 — In-App notifications (AgentResultNotification via database channel)
- [x] 10.5 — Email notifications (AgentResultNotification via mail channel for critical alerts)
- [x] 10.6 — ProactiveChatInjector (opt-in via AlertPreference, max 3/day Redis counter, system messages)
- [x] 10.7 — 5 scheduled jobs: DailyRiskScan(20h), MarketBriefing(9h), WeeklyReport(Fri 18h), RebalancingCheck(20:30), TaxAlert(monthly 1st)
- [x] 10.8 — 4 event-driven listeners: CompraDistribuida, Rebalanceamento A/B, ClienteAderiu, ValorMensalAlterado
- [x] 10.9 — AlertPreferences Livewire (toggle triggers, channel selection per trigger type)
- [x] 10.10 — NotificationBell (badge + dropdown) + NotificationFeed (filter by priority, mark as read)
- [x] 10.11 — Tests (9 new: 4 Prioritizer, 1 Dispatcher, 1 ChatInjector, 1 Job, 1 Preferences, 1 Feed)

---

## [x] Sprint 11 — Finalização
**Objetivo:** Qualidade, documentação e entrega

- [x] 11.1 — CI coverage enforced (removed continue-on-error on --min=70)
- [x] 11.2 — 6 ADRs: DDD Architecture, CQRS/ES, AI Multi-Provider, Agent Orchestrator, OWASP 2025, pgvector
- [x] 11.3 — Swagger/OpenAPI generated and validated (l5-swagger:generate)
- [x] 11.4 — Observability: JsonFormatter (structured JSON logs), RequestIdMiddleware (X-Request-Id correlation), agent_activity log channel
- [x] 11.5 — README complete: all 18 API routes documented, Swagger UI link, ADRs link
- [ ] 11.6 — Vídeo demonstração (pendente — deliverable do utilizador)

---

## Reference Docs

- [System Design Spec](superpowers/specs/2026-03-15-stock-purchase-system-design.md)
- [Finance Agents Design Spec](superpowers/specs/2026-03-15-finance-agents-design.md)
- [Business Rules (RN-001 to RN-070)](teste_itau_v2-main/regras-negocio-detalhadas.md)
- [API Contracts](teste_itau_v2-main/exemplos-contratos-api.md)
- [COTAHIST Layout](teste_itau_v2-main/layout-cotahist-b3.md)
- [Glossary](teste_itau_v2-main/glossario-compra-programada.md)
