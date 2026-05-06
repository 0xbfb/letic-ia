# FEATURE_EMBEDDINGS

## Objetivo
Implementar geração de embeddings para `DocumentChunk` com provider LLM configurado, persistindo vetor no PostgreSQL com `pgvector`.

## Modelo usado
- Provider: `openai`
- Modelo padrão: `text-embedding-3-small`
- Dimensão padrão: `1536`

## Variáveis de ambiente
```env
LLM_PROVIDER=openai
OPENAI_API_KEY=
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
OPENAI_EMBEDDING_DIMENSIONS=1536
```

## Fluxo
1. Documento com chunks disponíveis recebe action **Gerar embeddings** no Filament.
2. `GenerateDocumentEmbeddingsJob` seleciona apenas chunks sem embedding (`whereNull`).
3. `DocumentEmbeddingService` chama `LlmClientInterface` via `OpenAiClient`.
4. Valida dimensão do vetor retornado.
5. Persiste vetor em `document_chunks.embedding` (tipo `vector(N)`).
6. Registra execução em `llm_runs` (`operation=generate_embedding`, status, erro, duração, tokens quando disponível).
7. Ao concluir sem pendências/falhas, documento vai para `embedded`.

## Como testar
1. Rodar migrations.
2. Garantir documento em status `chunked` com chunks existentes e `embedding = null`.
3. Definir `OPENAI_API_KEY` válida.
4. Acionar **Gerar embeddings** no Filament.
5. Validar:
   - chunks receberam vetor;
   - `llm_runs` recebeu registros `success`;
   - status final do documento virou `embedded`.
6. Testar sem `OPENAI_API_KEY` e validar:
   - registros `llm_runs` com `failed`;
   - mensagem de erro explícita no log.

## Limitações (custo/API)
- Cada chunk gera 1 chamada ao endpoint de embeddings (custo por volume de chunks).
- Rate limit e indisponibilidade de API podem causar falhas por chunk.
- O job mantém resiliência por chunk (falha isolada), mas documento finaliza como `failed` se houver erro.
- Reprocessamento não duplica chunks: só processa chunks sem embedding.
