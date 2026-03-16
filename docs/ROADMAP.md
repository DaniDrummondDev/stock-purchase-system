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

## [ ] Sprint 8b — Recommendation + Portfolio Agent
**Objetivo:** Recomendação inteligente de cesta + análise de carteira

- [ ] 8b.1 — EmbeddingService (gera embeddings via laravel/ai)
- [ ] 8b.2 — RecommendationService + pgvector similarity search
- [ ] 8b.3 — PortfolioAnalystAgent (wraps recommendation logic)
- [ ] 8b.4 — Endpoint: POST /api/ai/recomendacao-cesta
- [ ] 8b.5 — Endpoint: POST /api/ai/chat (basic)
- [ ] 8b.6 — Interface admin Livewire (sugestão IA vs cesta atual)
- [ ] 8b.7 — Job scheduled: atualizar embeddings com novas cotações
- [ ] 8b.8 — Tests

---

## [ ] Sprint 9a — Risk + Tax + Market Agents
**Objetivo:** Análise de risco, IR e inteligência de mercado

- [ ] 9a.1 — RiskAnalysisService + RiskAnalystAgent — Score 0.0-1.0, faixas, alertas
- [ ] 9a.2 — Cache em analise_risco_cache (TTL 24h)
- [ ] 9a.3 — Publicação alertas-risco Kafka (score > 0.7)
- [ ] 9a.4 — TaxAnalystAgent — Consumes operacoes_ir + Kafka events
- [ ] 9a.5 — BcbProvider (Selic, IPCA, câmbio) — API pública BCB, cache 6h
- [ ] 9a.6 — MarketIntelligenceAgent
- [ ] 9a.7 — Rate limiting + resilience for external providers
- [ ] 9a.8 — Tests

## [ ] Sprint 9b — Assistant + Simulator + Educator
**Objetivo:** Assistente virtual completo com simulação e educação

- [ ] 9b.1 — ChatAssistantService + RAG (pgvector)
- [ ] 9b.2 — SimulatorAgent (what-if projections)
- [ ] 9b.3 — EducatorAgent (contextual financial education)
- [ ] 9b.4 — ChatWindow Livewire component (streaming)
- [ ] 9b.5 — chat_messages migration (normalizes chat_contextos.mensagens)
- [ ] 9b.6 — Guardrails (scope limiter)
- [ ] 9b.7 — Tests

---

## [ ] Sprint 10 — Notifications + Triggers
**Objetivo:** Sistema de notificações e triggers automáticos

- [ ] 10.1 — alert_preferences migration + CRUD API
- [ ] 10.2 — NotificationPrioritizer (critical/normal/low)
- [ ] 10.3 — AgentNotificationDispatcher
- [ ] 10.4 — In-App notifications (Laravel Notifications)
- [ ] 10.5 — Email notifications (critical alerts)
- [ ] 10.6 — ProactiveChatInjector (opt-in, max 3/day)
- [ ] 10.7 — Scheduled jobs: DailyRiskScan, MarketBriefing, WeeklyReport, RebalancingCheck, TaxAlert
- [ ] 10.8 — Event-driven triggers (CompraDistribuida, CotacoesImportadas, etc.)
- [ ] 10.9 — AlertPreferences Livewire component
- [ ] 10.10 — NotificationBell + NotificationFeed Livewire
- [ ] 10.11 — Tests

---

## [ ] Sprint 11 — Finalização
**Objetivo:** Qualidade, documentação e entrega

- [ ] 11.1 — Coverage de testes >= 70% (ajustar gaps)
- [ ] 11.2 — Documentação de arquitetura (ADRs)
- [ ] 11.3 — Swagger/OpenAPI completo e validado
- [ ] 11.4 — Observabilidade (logs estruturados, métricas)
- [ ] 11.5 — README completo com instruções de setup
- [ ] 11.6 — Vídeo demonstração (funcionalidade + arquitetura + decisões)

---

## Reference Docs

- [System Design Spec](superpowers/specs/2026-03-15-stock-purchase-system-design.md)
- [Finance Agents Design Spec](superpowers/specs/2026-03-15-finance-agents-design.md)
- [Business Rules (RN-001 to RN-070)](teste_itau_v2-main/regras-negocio-detalhadas.md)
- [API Contracts](teste_itau_v2-main/exemplos-contratos-api.md)
- [COTAHIST Layout](teste_itau_v2-main/layout-cotahist-b3.md)
- [Glossary](teste_itau_v2-main/glossario-compra-programada.md)
