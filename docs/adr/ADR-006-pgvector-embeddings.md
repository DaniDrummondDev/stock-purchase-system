# ADR-006: pgvector for Embeddings and Vector Search

## Status
Accepted

## Context
The AI bounded context needs vector similarity search for recommendation features (similar tickers, portfolio clustering) and RAG (Retrieval-Augmented Generation) for the chat assistant context. Evaluated options: pgvector (PostgreSQL extension), Pinecone, Weaviate, and Qdrant.

## Decision
Use the `pgvector` extension on the existing PostgreSQL 16 instance. Specific choices:

- **Column type**: `vector(1024)` for `ativo_embeddings` and `chat_contextos` tables.
- **Index**: HNSW (Hierarchical Navigable Small World) for cosine distance, providing fast approximate nearest neighbor (ANN) search.
- **Embedding generation**: Voyage AI (default provider) through `laravel/ai` SDK, allowing provider substitution via ADR-003 configuration hierarchy.
- **No additional infrastructure**: Vector data lives alongside relational data in the same PostgreSQL database.

## Consequences
### Positive
- No additional infrastructure to deploy, monitor, or maintain — pgvector runs as a PostgreSQL extension.
- Same database for relational and vector data simplifies transactions and joins (e.g., join embedding results with ticker metadata).
- HNSW index provides fast ANN search with configurable accuracy/speed tradeoff.
- Lower operational complexity compared to dedicated vector databases.

### Negative
- PostgreSQL is not optimized for massive vector workloads (millions of high-dimensional vectors).
- Limited to single-node performance — no native distributed vector search.
- Dimension is locked to 1024 in the column definition; changing requires migration.

### Risks
- As embedding count grows, HNSW index memory usage may impact overall PostgreSQL performance.
- Voyage AI embedding model changes could produce incompatible dimensions, requiring re-embedding of all data.
- Cosine distance may not be optimal for all similarity use cases; changing distance metric requires index rebuild.
