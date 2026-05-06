# FEATURE: Semantic Search com pgvector

## Objetivo
Implementar busca semântica sobre `document_chunks` já embeddados, recebendo uma consulta em texto e retornando os chunks mais relevantes.

## Implementação

### Serviço
Arquivo: `app/Services/Documents/DocumentSearchService.php`

Fluxo do método `search(string $query, int $limit = 8, ?array $documentIds = null): Collection`:
1. normaliza e valida query;
2. gera embedding da consulta via provider LLM configurado;
3. filtra chunks com `embedding IS NOT NULL`;
4. aplica filtro opcional por `source_document_id`;
5. calcula distância e similaridade via operador de cosseno do pgvector (`<=>`);
6. ordena por menor distância (mais relevante);
7. aplica limite;
8. registra execução em `llm_runs` com status `success` ou `failed`.

### SQL usado
Consulta equivalente:

```sql
SELECT
  dc.id,
  dc.source_document_id,
  sd.title AS document_title,
  dc.chunk_index,
  dc.content,
  dc.embedding <=> :query_vector::vector AS distance,
  1 - (dc.embedding <=> :query_vector::vector) AS similarity
FROM document_chunks dc
JOIN source_documents sd ON sd.id = dc.source_document_id
WHERE dc.embedding IS NOT NULL
  AND (:document_ids IS NULL OR dc.source_document_id IN (...))
ORDER BY distance ASC
LIMIT :limit;
```

## Comando Artisan
Arquivo: `app/Console/Commands/DocumentSearchCommand.php`

Comando criado:

```bash
php artisan documents:search "texto de busca"
```

Opções:

```bash
php artisan documents:search "seo para blog" --limit=5
php artisan documents:search "palavra-chave" --document_ids=1,2,7
php artisan documents:search "topic map" --limit=10 --document_ids=3,4
```

Saída inclui:
- chunk id
- source_document_id
- título do documento
- chunk_index
- distância
- similaridade
- trecho de conteúdo para validação rápida

## Preview no painel (Filament)
Adicionada action `Preview busca semântica` em `SourceDocumentResource`:
- campo de consulta;
- campo de limite;
- exibe notificação persistente com os chunks relevantes.

## Como testar
1. Garantir que os chunks tenham embeddings válidos no PostgreSQL (`vector(N)`).
2. Executar busca:
   ```bash
   php artisan documents:search "sua consulta"
   ```
3. Validar:
   - resultado ordenado por relevância;
   - limite aplicado;
   - chunks sem embedding ignorados;
   - filtro por `--document_ids` funcionando.
4. Testar erro:
   - remover/chave inválida de API e verificar mensagem clara de falha.

## Observações sobre pgvector
- Esta implementação usa **cosine distance** com operador `<=>`.
- Similaridade é derivada como `1 - distance`.
- O operador exige consistência da dimensão do vetor entre:
  - coluna `document_chunks.embedding` (`vector(N)`);
  - configuração de embeddings (`OPENAI_EMBEDDING_DIMENSIONS`).
- Em bases grandes, considerar índice vetorial (ex.: ivfflat/hnsw) em etapa futura.
