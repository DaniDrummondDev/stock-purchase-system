# Sistema de Compra Programada de Ações — Design Spec

## Contexto

Este projeto é uma adaptação do desafio técnico de Compra Programada de Ações (originalmente .NET/C#) para **PHP/Laravel + PostgreSQL (pgvector) + Kafka**. O sistema permite que clientes de uma corretora assinem um plano de investimento recorrente em uma carteira recomendada de 5 ações (Top Five), com compras automatizadas, cálculo de IR, e rebalanceamento de carteira.

**Diferencial:** Integração de IA em 3 áreas — recomendação de cesta, análise de risco, e assistente virtual (chatbot).

---

## Arquitetura: Monolito Modular DDD

### Decisão
Monolito Laravel organizado em **Bounded Contexts** como módulos internos, com CQRS (Command/Query Separation) e Event Sourcing via `spatie/laravel-event-sourcing`.

### Justificativa
- Demonstra domínio de DDD, CQRS e Event Sourcing sem complexidade desnecessária de microserviços
- Deploy simples, coesão arquitetural
- IA integrada naturalmente como Bounded Context próprio
- Kafka usado para eventos de IR (requisito) e alertas de risco (diferencial)

---

## Estrutura do Projeto

```
stock-purchase-system/
├── docker-compose.yml
├── cotacoes/                       # Arquivos COTAHIST B3
├── app/
│   ├── Domain/                     # Bounded Contexts (domínio puro)
│   │   ├── Client/                 # Cliente, Conta Gráfica, Custódia
│   │   │   ├── Entities/
│   │   │   ├── ValueObjects/       # CPF, Email
│   │   │   ├── Events/             # ClienteAderiu, ClienteSaiu, ValorMensalAlterado
│   │   │   ├── Repositories/       # Interfaces
│   │   │   └── Services/
│   │   ├── Basket/                 # Cesta Top Five
│   │   │   ├── Entities/
│   │   │   ├── ValueObjects/       # Percentual, TickerCollection
│   │   │   ├── Events/             # CestaCriada, CestaAlterada
│   │   │   ├── Repositories/
│   │   │   └── Services/
│   │   ├── PurchaseEngine/         # Motor de Compra Programada
│   │   │   ├── Aggregates/         # CompraConsolidada (Event Sourced)
│   │   │   ├── ValueObjects/       # Money, Quantidade, LotePadrao
│   │   │   ├── Events/             # CompraConsolidada, CompraDistribuida
│   │   │   ├── Repositories/
│   │   │   └── Services/           # DistribuicaoService, ResiduoService
│   │   ├── Rebalancing/            # Motor de Rebalanceamento
│   │   │   ├── Entities/
│   │   │   ├── Events/             # RebalanceamentoTipoA, RebalanceamentoTipoB
│   │   │   ├── Repositories/
│   │   │   └── Services/           # RebalanceamentoTipoAService, TipoBService
│   │   ├── MarketData/             # Cotações B3
│   │   │   ├── Entities/           # Cotacao
│   │   │   ├── ValueObjects/       # Ticker, PrecoFechamento
│   │   │   ├── Repositories/
│   │   │   └── Services/           # CotahistParserService
│   │   ├── Tax/                    # Impostos
│   │   │   ├── Entities/           # OperacaoIR
│   │   │   ├── ValueObjects/
│   │   │   ├── Events/             # IRDedoDuroCalculado, IRVendaCalculado
│   │   │   └── Services/           # DedoDuroService, IRVendaService
│   │   └── AI/                     # Bounded Context de IA
│   │       ├── Recommendation/     # Recomendação de Cesta
│   │       │   ├── Entities/
│   │       │   ├── Services/       # RecommendationService
│   │       │   └── ValueObjects/
│   │       ├── RiskAnalysis/       # Análise de Risco
│   │       │   ├── Entities/
│   │       │   ├── Services/       # RiskAnalysisService
│   │       │   └── ValueObjects/
│   │       └── Assistant/          # Assistente Virtual
│   │           ├── Entities/
│   │           ├── Services/       # ChatAssistantService
│   │           └── ValueObjects/
│   ├── Application/                # CQRS Layer
│   │   ├── Commands/
│   │   │   ├── AderirClienteCommand.php
│   │   │   ├── SairClienteCommand.php
│   │   │   ├── AlterarValorMensalCommand.php
│   │   │   ├── CriarCestaCommand.php
│   │   │   ├── ExecutarCompraCommand.php
│   │   │   ├── ExecutarRebalanceamentoCommand.php
│   │   │   └── EnviarMensagemChatCommand.php
│   │   ├── Queries/
│   │   │   ├── ObterCarteiraQuery.php
│   │   │   ├── ObterRentabilidadeQuery.php
│   │   │   ├── ObterCestaAtualQuery.php
│   │   │   ├── ObterHistoricoCestaQuery.php
│   │   │   ├── ObterCustodiaMasterQuery.php
│   │   │   ├── ObterAnaliseRiscoQuery.php
│   │   │   └── ObterRecomendacaoCestaQuery.php
│   │   └── Handlers/
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   │   ├── Models/             # Eloquent Models
│   │   │   ├── Repositories/       # Implementações concretas
│   │   │   └── Projections/        # Event Sourcing projections
│   │   ├── Kafka/
│   │   │   ├── KafkaProducer.php
│   │   │   ├── Messages/           # IRDedoDuroMessage, IRVendaMessage
│   │   │   └── Config/
│   │   ├── AI/
│   │   │   └── EmbeddingService.php  # Usa laravel/ai internamente
│   │   └── B3/
│   │       └── CotahistFileParser.php
│   └── Presentation/
│       ├── Http/
│       │   └── Controllers/
│       │       └── Api/
│       │           ├── ClienteController.php
│       │           ├── AdminCestaController.php
│       │           ├── ContaMasterController.php
│       │           ├── MotorController.php
│       │           └── AIController.php
│       ├── Livewire/
│       │   ├── Dashboard/          # Dashboard do cliente
│       │   ├── Admin/              # Painel admin
│       │   ├── Portfolio/          # Carteira e rentabilidade
│       │   └── Chat/               # Assistente virtual
│       └── Views/
│           ├── layouts/
│           ├── dashboard/
│           ├── admin/
│           └── components/
├── tests/
│   ├── Unit/                       # Testes de domínio (>70% coverage)
│   ├── Integration/                # Testes com DB/Kafka
│   └── Feature/                    # Testes de API end-to-end
└── docs/
    ├── teste_itau_v2-main/         # Especificação original do desafio
    ├── architecture/               # ADRs, diagramas
    └── sprints/                    # Roadmap e sprints
```

---

## Modelo de Dados

### Tabelas Core

#### `clientes`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador único |
| nome | varchar(255) | Nome completo |
| cpf | varchar(11) | CPF (unique) |
| email | varchar(255) | Email |
| valor_mensal | decimal(12,2) | Valor do aporte mensal (min R$ 100) |
| status | enum(ativo,inativo) | Status de participação |
| valor_total_investido | decimal(15,2) | Valor acumulado investido (RN-063 a RN-070) |
| created_at | timestamp | Data de adesão |
| updated_at | timestamp | Última atualização |

#### `contas_graficas`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| cliente_id | uuid (FK) | Referência ao cliente |
| numero | varchar(20) | Número da conta gráfica |
| created_at | timestamp | Data de criação |

> **Nota:** A conta gráfica é um registro formal de vínculo do cliente com a corretora. Não possui saldo em dinheiro — o valor mensal do cliente é usado diretamente nas compras programadas.

#### `cestas`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| nome | varchar(255) | Nome da cesta (ex: "Top Five - Março 2026") |
| ativo | boolean | Se é a cesta vigente |
| data_desativacao | timestamp nullable | Data em que foi desativada (RN-017) |
| created_at | timestamp | Data de criação |

#### `cesta_ativos`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| cesta_id | uuid (FK) | Referência à cesta |
| ticker | varchar(12) | Código do ativo (ex: PETR4) |
| percentual | decimal(5,2) | Percentual na cesta (soma = 100%) |

#### `custodias`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| cliente_id | uuid (FK) | Referência ao cliente |
| ticker | varchar(12) | Código do ativo |
| quantidade | integer | Quantidade de ações |
| preco_medio | decimal(12,2) | Preço médio de aquisição |

#### `custodia_master`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| ticker | varchar(12) | Código do ativo |
| quantidade | integer | Quantidade residual |
| preco_medio | decimal(12,2) | Preço médio |

#### `cotacoes`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | bigint (PK) | Identificador |
| ticker | varchar(12) | Código do ativo |
| data_pregao | date | Data do pregão |
| preco_fechamento | decimal(12,2) | Preço de fechamento |
| preco_abertura | decimal(12,2) | Preço de abertura |
| preco_maximo | decimal(12,2) | Máxima do dia |
| preco_minimo | decimal(12,2) | Mínima do dia |
| tipo_mercado | enum(padrao,fracionario) | Lote padrão ou fracionário |
| cod_bdi | varchar(2) | Código BDI (02=Lote Padrão, 96=Fracionário) |
| volume | decimal(18,2) | Volume negociado (VOLTOT do COTAHIST) |
| unique(ticker, data_pregao, tipo_mercado) | | Índice composto |

#### `compra_participantes`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| compra_id | uuid (FK) | Referência à compra programada |
| cliente_id | uuid (FK) | Referência ao cliente |
| valor_aporte | decimal(12,2) | Valor do aporte do cliente nesta execução (capturado no momento da compra) |

#### `compras_programadas`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| data_execucao | date | Data de execução |
| status | enum(pendente,processando,concluida,erro) | Status |
| valor_total | decimal(15,2) | Valor total consolidado |
| created_at | timestamp | Timestamp |

#### `compra_distribuicoes`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| compra_id | uuid (FK) | Referência à compra |
| cliente_id | uuid (FK) | Referência ao cliente |
| ticker | varchar(12) | Ativo comprado |
| quantidade | integer | Quantidade distribuída |
| valor | decimal(12,2) | Valor da operação |
| preco_unitario | decimal(12,2) | Preço unitário |
| tipo_lote | enum(padrao,fracionario) | Tipo do lote |
| data_pregao | date | Data da cotação utilizada (auditoria) |

#### `operacoes_ir`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| cliente_id | uuid (FK) | Referência ao cliente |
| tipo | enum(dedo_duro,venda) | Tipo de operação IR |
| ticker | varchar(12) | Ativo |
| valor_operacao | decimal(12,2) | Valor da operação |
| imposto | decimal(12,2) | Valor do imposto |
| mes_referencia | varchar(7) | Mês (YYYY-MM) |
| publicado_kafka | boolean | Se foi publicado no Kafka |
| created_at | timestamp | Timestamp |

#### `audit_logs`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | bigint (PK) | Identificador |
| auditable_type | varchar(255) | Tipo do model |
| auditable_id | uuid | ID do model |
| event | enum(created,updated,deleted) | Tipo de evento |
| old_values | jsonb | Valores anteriores |
| new_values | jsonb | Valores novos |
| user_id | uuid nullable | Usuário responsável |
| ip_address | varchar(45) | IP da requisição |
| created_at | timestamp | Timestamp |

### Tabelas Event Sourcing (spatie)

#### `stored_events`
Tabela gerida automaticamente pelo `spatie/laravel-event-sourcing`.

### Tabelas IA (pgvector)

#### `ativo_embeddings`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | bigint (PK) | Identificador |
| ticker | varchar(12) | Código do ativo |
| embedding | vector(1536) | Embedding vetorial (pgvector) |
| metadata | jsonb | Volatilidade, retorno, volume |
| data_referencia | date | Data de referência dos dados |
| created_at | timestamp | Timestamp |

#### `chat_contextos`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| cliente_id | uuid (FK) | Referência ao cliente |
| mensagens | jsonb | Histórico de mensagens |
| embedding | vector(1536) | Embedding do contexto |
| created_at | timestamp | Timestamp |
| updated_at | timestamp | Última atualização |

#### `analise_risco_cache`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| cliente_id | uuid (FK) | Referência ao cliente |
| score_risco | decimal(3,2) | Score de 0 a 1 |
| alertas | jsonb | Lista de alertas |
| recomendacoes | text | Recomendações em linguagem natural |
| valid_until | timestamp | Validade do cache |
| created_at | timestamp | Timestamp |

#### `ai_configurations`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | uuid (PK) | Identificador |
| scope | enum(global,user) | Escopo da configuração |
| user_id | uuid (FK) nullable | Referência ao cliente (null se global) |
| provider | varchar(50) | Nome do provider (anthropic, openai, gemini...) |
| purpose | varchar(30) | Finalidade (llm, embeddings) |
| api_key | text (encrypted) | API key encriptada via Laravel Crypt |
| settings | jsonb | Configurações extras do provider (modelo, URL base, etc.) |
| is_active | boolean | Se está ativa |
| validated_at | timestamp nullable | Última validação bem-sucedida |
| unique(scope, user_id, purpose) | | Um config por escopo/user/finalidade |

---

## Integração de IA

### 3 Áreas de IA como Bounded Context

#### 1. Recomendação de Cesta Top Five (`Domain/AI/Recommendation`)
- **Input:** Dados históricos COTAHIST processados
- **Processo:** Gera embeddings vetoriais (via Voyage AI) para cada ativo baseados em volatilidade histórica, retorno médio, volume, correlações
- **pgvector:** Busca por similaridade para encontrar ativos diversificados (vetores distantes = baixa correlação)
- **Output:** 5 ativos sugeridos com percentuais e justificativa textual
- **Endpoint:** `POST /api/ai/recomendacao-cesta`
- **Uso:** Apoio à decisão do admin antes de alterar a cesta

#### 2. Análise de Risco (`Domain/AI/RiskAnalysis`)
- **Input:** Carteira do cliente, dados de cotação, composição da cesta
- **Processo:** Analisa concentração, volatilidade, desvio da composição ideal, exposição
- **Alertas:** Concentração >40% em um ativo, volatilidade acima do perfil
- **Output:** Score de risco (0-1), alertas, recomendações em linguagem natural
- **Endpoint:** `GET /api/ai/clientes/{id}/analise-risco`
- **Kafka:** Publica alertas críticos no tópico `alertas-risco`

#### 3. Assistente Virtual (`Domain/AI/Assistant`)
- **Input:** Mensagem do cliente + contexto (carteira, regras de negócio, glossário)
- **Processo:** RAG — pgvector busca contexto relevante, Claude API gera resposta
- **Escopo:** Consultas sobre carteira, IR, rebalanceamento, glossário
- **Endpoint:** `POST /api/ai/chat` com `{cliente_id, mensagem}`
- **Frontend:** Componente Livewire de chat no dashboard do cliente

### Stack de IA
- **SDK:** `laravel/ai` (v0.3+) — SDK oficial do Laravel, multi-provider, com suporte nativo a pgvector
- **LLM (default):** Anthropic (Claude) — geração de texto, análise, respostas do chatbot. Trocável via `AI_DEFAULT_PROVIDER` env var
- **Embeddings (default):** Voyage AI — geração de embeddings. Trocável via `AI_DEFAULT_EMBEDDINGS_PROVIDER` env var
- **Vetores:** pgvector extension para PostgreSQL — busca por similaridade via `laravel/ai` Eloquent integration (`whereVectorSimilarTo`)
- **RAG:** Documentação de regras de negócio + dados do cliente como contexto vetorial
- **Multi-provider:** Suporta OpenAI, Gemini, Ollama, Mistral, Groq, DeepSeek, etc. sem mudança de código

### Configuração Dinâmica de Providers (Hierárquica)
- **Tabela `ai_configurations`:** Armazena provider + API key (encriptada) + purpose (llm/embeddings)
- **Escopo hierárquico:** user config → global config → `.env` fallback
- **Admin:** Configura o provider/key default global para toda a plataforma
- **Cliente:** Pode sobrescrever com seu próprio provider/key se desejar
- **UI Settings:** Página Livewire com seleção de provider, input de API key, botão "Testar Conexão"
- **`AiConfigResolver`:** Service que resolve qual provider usar em runtime baseado na hierarquia
- **Providers disponíveis:** Anthropic, OpenAI, Gemini, Ollama, Mistral, Groq, DeepSeek, Voyage AI, OpenRouter, xAI

---

## Regras de Negócio Críticas

### Preço Médio (PM)
- **RN-041/042:** `PM = (QtyOld × PMOld + QtyNew × PriceNew) / (QtyOld + QtyNew)` — recalculado a cada compra
- **RN-043:** Em vendas (rebalanceamento), o PM **NÃO** muda, apenas a quantidade diminui
- **RN-044:** PM é recalculado **somente** em compras

### Rentabilidade (RN-063 a RN-070)
- Saldo total = Σ (quantidade × cotação atual) para cada ativo
- P/L por ativo = (cotação atual - PM) × quantidade
- P/L total = Σ P/L de todos os ativos
- Rentabilidade % = ((saldo atual - valor total investido) / valor total investido) × 100
- Composição real % = (valor do ativo / saldo total) × 100
- Exibir: PM, quantidade, cotação atual, valor atual, P/L, composição %

### Rebalanceamento Tipo B — Decisão de Threshold
- **Threshold:** 5 pontos percentuais de desvio da composição alvo
- **Trigger:** Scheduled job diário (após atualização de cotações), verifica se algum ativo desviou > 5pp
- **Ação:** Vende ativos sobre-representados e compra sub-representados para retornar à composição alvo

### Integração entre Bounded Contexts: Rebalanceamento → Tax
- Rebalanceamento emite domain events (`AtivoVendidoPorRebalanceamento`) ao vender ativos
- O Bounded Context `Tax` subscreve esses eventos via listener
- `Tax` calcula IR Dedo-Duro (compras) e IR Venda (vendas do rebalanceamento) e publica no Kafka
- Interface: `TaxableOperation` — valor, ticker, quantidade, preço unitário, tipo (compra/venda), cliente_id
- O mesmo fluxo vale para `PurchaseEngine → Tax` nas compras regulares

### Saída do Cliente e `valor_total_investido`
- Quando o cliente sai (RN-007 a RN-010), o `valor_total_investido` **permanece congelado**
- O cliente mantém acesso à carteira e pode consultar rentabilidade usando o valor congelado
- Novas compras não são realizadas, mas o campo permanece para cálculos históricos

### Cotações — Staleness
- O motor de compra usa a **cotação de fechamento mais recente disponível** no banco
- Não há threshold de staleness — se a última cotação disponível é de sexta-feira e a compra roda na segunda, usa-se a de sexta
- O campo `data_pregao` da cotação é registrado na `compra_distribuicoes` para auditoria

### Histórico de Valor Mensal (RN-013)
- O histórico de alterações do `valor_mensal` é rastreado automaticamente via `owen-it/laravel-auditing` na tabela `audit_logs`
- Cada alteração gera um registro com `old_values` e `new_values` contendo o valor anterior e o novo
- Não é necessária tabela dedicada — o audit trail satisfaz este requisito

### Conta Master
- A conta master não tem tabela própria — é representada implicitamente pela tabela `custodia_master`
- A `custodia_master` armazena apenas resíduos (frações que sobraram após distribuição)
- Os resíduos são considerados antes de novas compras (RN-037)

### Event Sourcing — Escopo
- **Event Sourced:** Apenas `PurchaseEngine/Aggregates/CompraConsolidada` — armazena todo o ciclo de compra como eventos
- **Eloquent padrão:** Todos os demais agregados (Client, Basket, etc.) usam Eloquent com audit trail via `owen-it/laravel-auditing`

---

## Contratos de API — Erros e Respostas

### Códigos de Erro
| Código | HTTP | Descrição |
|--------|------|-----------|
| CLIENTE_CPF_DUPLICADO | 409 | CPF já cadastrado |
| VALOR_MENSAL_INVALIDO | 422 | Valor mensal < R$ 100 |
| CLIENTE_NAO_ENCONTRADO | 404 | Cliente não existe |
| CLIENTE_JA_INATIVO | 422 | Cliente já saiu do programa |
| PERCENTUAIS_INVALIDOS | 422 | Soma dos percentuais ≠ 100% |
| QUANTIDADE_ATIVOS_INVALIDA | 422 | Cesta não tem exatamente 5 ativos |
| COTACAO_NAO_ENCONTRADA | 404 | Cotação não disponível para o ticker |
| COMPRA_JA_EXECUTADA | 409 | Compra já executada para esta data |
| KAFKA_INDISPONIVEL | 503 | Kafka não disponível para publicação |

### Formato de Resposta Padrão
```json
{
  "success": true,
  "data": { ... },
  "message": "Operação realizada com sucesso"
}
```

### Formato de Erro
```json
{
  "success": false,
  "error": {
    "code": "CLIENTE_CPF_DUPLICADO",
    "message": "Já existe um cliente cadastrado com este CPF"
  }
}
```

---

## Schemas de Mensagens Kafka

### Tipo 01 — IR Dedo-Duro (`ir-dedo-duro`)
```json
{
  "tipo": "IR_DEDO_DURO",
  "clienteId": "uuid",
  "cpf": "12345678901",
  "ticker": "PETR4",
  "tipoOperacao": "COMPRA",
  "quantidade": 10,
  "precoUnitario": 38.50,
  "valorOperacao": 385.00,
  "aliquota": 0.00005,
  "valorIR": 0.02,
  "dataOperacao": "2026-03-15",
  "dataCalculo": "2026-03-15T10:30:00Z"
}
```

### Tipo 02 — IR Venda (`ir-venda`)
> **Nota:** Campo `isento` é adição intencional ao contrato original para facilitar o consumo downstream.
```json
{
  "tipo": "IR_VENDA",
  "clienteId": "uuid",
  "cpf": "12345678901",
  "mesReferencia": "2026-03",
  "totalVendasMes": 25000.00,
  "isento": false,
  "lucroLiquido": 3500.00,
  "aliquota": 0.20,
  "valorIR": 700.00,
  "detalhes": [
    {
      "ticker": "VALE3",
      "quantidade": 50,
      "precoVenda": 85.00,
      "precoMedio": 72.00,
      "valorVenda": 4250.00,
      "lucro": 650.00
    }
  ],
  "dataCalculo": "2026-03-31T23:59:00Z"
}
```

---

## Infraestrutura Docker

### docker-compose.yml Services

| Serviço | Imagem | Porta | Função |
|---------|--------|-------|--------|
| `app` | PHP 8.3-fpm + Laravel | 8000 | Aplicação principal |
| `postgres` | PostgreSQL 16 + pgvector | 5432 | Banco de dados |
| `redis` | Redis 7 | 6379 | Cache, filas, sessões |
| `kafka` | confluentinc/cp-kafka | 9092 | Mensageria |
| `zookeeper` | confluentinc/cp-zookeeper | 2181 | Coordenação Kafka |
| `kafka-ui` | provectuslabs/kafka-ui | 8080 | Painel Kafka (dev) |
| `nginx` | nginx:alpine | 80 | Reverse proxy |

### Tópicos Kafka
- `ir-dedo-duro` — IR retido em cada compra distribuída (tipo 01)
- `ir-venda` — IR sobre vendas mensais (tipo 02)
- `alertas-risco` — Alertas de risco da IA
- `compra-executada` — Evento de compra consolidada

### Filas Laravel (Redis)
- `cotahist-parser` — Processamento assíncrono de arquivos COTAHIST
- `ai-analysis` — Jobs de análise de IA
- `purchase-engine` — Execução do motor de compra

---

## API Endpoints

### Clientes
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/clientes/adesao` | Adesão do cliente |
| POST | `/api/clientes/{id}/saida` | Saída do cliente |
| PUT | `/api/clientes/{id}/valor-mensal` | Alterar valor mensal |
| GET | `/api/clientes/{id}/carteira` | Consultar carteira |
| GET | `/api/clientes/{id}/rentabilidade` | Consultar rentabilidade |

### Admin
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/admin/cesta` | Criar/atualizar cesta Top Five |
| GET | `/api/admin/cesta/atual` | Cesta vigente |
| GET | `/api/admin/cesta/historico` | Histórico de cestas |
| GET | `/api/admin/conta-master/custodia` | Custódia da conta master |

### Motor
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/motor/executar-compra` | Executar compra (manual/teste) |

### IA
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/ai/recomendacao-cesta` | Sugestão de cesta pela IA |
| GET | `/api/ai/clientes/{id}/analise-risco` | Análise de risco do cliente |
| POST | `/api/ai/chat` | Chat com assistente virtual |

### Configuração de IA
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/ai/config` | Obter configuração ativa (global ou do usuário) |
| PUT | `/api/ai/config` | Criar/atualizar configuração de provider e API key |
| POST | `/api/ai/config/test` | Testar conexão com o provider configurado |
| GET | `/api/ai/providers` | Listar providers disponíveis |
| DELETE | `/api/ai/config` | Remover configuração do usuário (volta ao global) |

---

## Packages Laravel

| Package | Uso |
|---------|-----|
| `spatie/laravel-event-sourcing` | Event Sourcing + Projections |
| `owen-it/laravel-auditing` | Audit trail automático |
| `livewire/livewire` | Frontend reativo |
| `laravel/horizon` | Dashboard de filas |
| `phprdkafka` (ext) + wrapper | Producer/Consumer Kafka |
| `laravel/ai` | SDK oficial Laravel — multi-provider LLM, embeddings, pgvector nativo |
| `pestphp/pest` | Framework de testes |

---

## Roadmap de Sprints

### Sprint 0 — Fundação
**Objetivo:** Setup completo do ambiente de desenvolvimento
- Docker Compose (PostgreSQL+pgvector, Kafka, Zookeeper, Redis, Kafka UI)
- Projeto Laravel com estrutura DDD de pastas
- Configuração de packages (Event Sourcing, Auditing, Livewire, Pest)
- Migrations iniciais (todas as tabelas)
- CI base (GitHub Actions: lint, tests)
- CLAUDE.md com convenções do projeto
- Swagger/OpenAPI base

### Sprint 1 — Gestão de Clientes
**Objetivo:** CRUD completo de clientes com regras de negócio
- Regras: RN-001 a RN-013
- Entidades: Cliente, ContaGrafica, Custodia
- Value Objects: CPF, Email, Money
- Commands: AderirCliente, SairCliente, AlterarValorMensal
- Endpoints: POST adesão, POST saída, PUT valor-mensal, GET carteira
- Eventos: ClienteAderiu, ClienteSaiu, ValorMensalAlterado
- Testes unitários + feature tests

### Sprint 2 — Cesta Top Five
**Objetivo:** Gestão da cesta de ações recomendada
- Regras: RN-014 a RN-019
- Entidades: Cesta, CestaAtivo
- Validações: exatamente 5 ativos, soma = 100%
- Endpoints: POST criar/atualizar, GET atual, GET histórico
- Eventos: CestaCriada, CestaAlterada
- Painel admin básico (Livewire)
- Testes

### Sprint 3 — Market Data (COTAHIST)
**Objetivo:** Parser de arquivos COTAHIST B3 e cache de cotações
- Parser de arquivo fixed-width (245 chars/linha)
- Filtro por tipo de registro (01), BDI (02/96), mercado (010/020)
- Conversão de preços (inteiro com 2 decimais implícitos)
- Cache em tabela `cotacoes`
- Job assíncrono para processamento (fila `cotahist-parser`)
- Testes com arquivo COTAHIST de exemplo

### Sprint 4 — Motor de Compra Programada (Crítico)
**Objetivo:** Implementar o motor de compra consolidada e distribuição
- Regras: RN-020 a RN-044
- Datas de execução: 5, 15, 25 (próximo dia útil se fim de semana)
- Consolidação: soma aportes de todos os clientes ativos
- Compra consolidada usando cotação mais recente
- Separação lote padrão (100) vs fracionário (1-99)
- Distribuição proporcional por valor de aporte
- Gestão de resíduos na conta master
- Atualização de preço médio: PM recalculado em compras (RN-041/042), inalterado em vendas (RN-043/044)
- Atualização de `valor_total_investido` no cliente
- Registro de participantes em `compra_participantes`
- Event Sourcing: CompraConsolidada como aggregate
- Testes extensivos (caso mais complexo do sistema)

### Sprint 5 — IR e Kafka
**Objetivo:** Cálculo de impostos e publicação no Kafka
- Regras: RN-053 a RN-062
- IR Dedo-Duro: 0.005% sobre valor de cada operação distribuída
- IR Vendas: 20% sobre lucro líquido quando vendas > R$ 20k/mês
- Isenção: vendas ≤ R$ 20k/mês para pessoa física
- Producer Kafka para tópicos `ir-dedo-duro` e `ir-venda`
- Formato de mensagens conforme especificação (Tipo 01 e 02)
- Testes de integração com Kafka

### Sprint 6 — Rebalanceamento
**Objetivo:** Implementar os dois tipos de rebalanceamento
- Regras: RN-045 a RN-052
- **Tipo A:** Mudança de composição da cesta → vender ativos removidos, comprar novos
- **Tipo B:** Desvio de proporção > 5% → rebalancear dentro da cesta atual
- Trigger automático quando cesta é alterada (Tipo A)
- Análise periódica de desvio (Tipo B)
- Cálculo de IR sobre vendas do rebalanceamento
- Testes

### Sprint 7 — Frontend Livewire
**Objetivo:** Interface completa do sistema
- Regras de rentabilidade: RN-063 a RN-070
- **Dashboard Cliente:** Carteira com saldo total, P/L por ativo, P/L total, rentabilidade %, composição real %
- **Tela de Rentabilidade:** PM, quantidade, cotação atual, valor atual, valor total investido, evolução do patrimônio
- **Painel Admin:** Gestão de cesta, visualização de conta master, histórico
- Componentes reutilizáveis (tabelas, gráficos, cards)
- Responsivo (mobile-friendly)

### Sprint 8 — IA: Recomendação de Cesta
**Objetivo:** Sistema de recomendação inteligente
- `Infrastructure/AI/EmbeddingService.php` — gera embeddings via `laravel/ai` a partir de dados COTAHIST (volatilidade, retorno, volume por ticker)
- Busca por similaridade via Eloquent pgvector integration (`whereVectorSimilarTo`)
- `Domain/AI/Recommendation/Services/RecommendationService.php`:
  - Input: dados de mercado atuais
  - Busca os 5 ativos mais diversificados via pgvector (vetores distantes = baixa correlação)
  - Envia contexto para Claude API para gerar percentuais e justificativa textual
  - Output: `RecommendationResult` VO com tickers, percentuais, justificativa
- Endpoint: `POST /api/ai/recomendacao-cesta` → retorna sugestão com justificativa
- Interface admin Livewire para visualizar e comparar sugestão IA vs cesta atual
- Job scheduled para atualizar embeddings quando novas cotações são importadas
- Testes unitários do service + testes de integração com pgvector

### Sprint 9 — IA: Análise de Risco e Assistente Virtual
**Objetivo:** Completar as funcionalidades de IA
- **Análise de Risco:**
  - `Domain/AI/RiskAnalysis/Services/RiskAnalysisService.php`:
    - Input: carteira do cliente (custódias + cotações atuais + composição da cesta)
    - Calcula: concentração por ativo, volatilidade (desvio padrão dos retornos), desvio da composição alvo
    - Envia métricas para Claude API para gerar score (0-1), alertas e recomendações em linguagem natural
    - Output: `RiskAnalysisResult` VO com score, alertas[], recomendações
  - Cache de resultados em `analise_risco_cache` (TTL: 24h)
  - Publicação de alertas críticos (score > 0.7) no tópico Kafka `alertas-risco`
- **Chatbot RAG:**
  - `Domain/AI/Assistant/Services/ChatAssistantService.php`:
    - Input: mensagem do cliente + cliente_id
    - Carrega contexto: carteira atual, regras de negócio (glossário + regras relevantes via pgvector), última análise de risco
    - Envia para Claude API com system prompt do domínio
    - Output: resposta contextualizada + atualização do histórico em `chat_contextos`
  - Componente Livewire `Chat/ChatWindow.php` com streaming de resposta
  - Guardrails: limitar escopo às perguntas sobre carteira, IR, rebalanceamento
- Testes unitários dos services + testes de integração

### Sprint 10 — Finalização
**Objetivo:** Qualidade, documentação e entrega
- Coverage de testes ≥ 70% (ajustar gaps)
- Documentação de arquitetura (ADRs)
- Swagger/OpenAPI completo e validado
- Observabilidade (logs estruturados, métricas básicas)
- README completo com instruções de setup
- Vídeo demonstração (funcionalidade + arquitetura + decisões)

---

## Verificação

### Como testar end-to-end
1. `docker-compose up -d` — subir todos os serviços
2. `php artisan migrate` — criar tabelas
3. Importar arquivo COTAHIST de exemplo
4. Criar cesta Top Five via API/admin
5. Cadastrar clientes via API
6. Executar motor de compra via `POST /api/motor/executar-compra`
7. Verificar distribuição, preço médio, resíduos
8. Verificar mensagens nos tópicos Kafka (via Kafka UI)
9. Testar rebalanceamento alterando a cesta
10. Testar funcionalidades de IA (recomendação, risco, chat)
11. `php artisan test --coverage` — verificar ≥ 70%

### Comandos de teste
```bash
# Testes unitários
php artisan test --testsuite=Unit

# Testes de integração
php artisan test --testsuite=Integration

# Testes feature (API)
php artisan test --testsuite=Feature

# Coverage completo
php artisan test --coverage --min=70
```
