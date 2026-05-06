# FEATURE_TESTS

## Objetivo
Cobrir regressões básicas do fluxo principal do MVP sem buscar cobertura total.

## Testes criados

### Documentos
- `tests/Feature/DocumentFlowTest.php`
  - cria `SourceDocument`
  - valida limite de `file_type` (17 chars gera erro de banco)
  - valida chunking usando `DocumentChunkerService` + persistência em `document_chunks`

### Briefings
- `tests/Feature/BriefFlowTest.php`
  - cria `ContentBrief`
  - valida campos obrigatórios (erro ao persistir briefing incompleto)

### Posts
- `tests/Feature/PostFlowTest.php`
  - cria `GeneratedPost`
  - edição gera nova versão via `PostVersionService`
  - checklist SEO cria `SeoAudit` via `SeoAuditService`

### WordPress
- `tests/Feature/WordPressClientTest.php`
  - valida payload de draft enviado para endpoint `/wp-json/wp/v2/posts`
  - mock de HTTP com `Http::fake()`
  - trata erro HTTP com `WordPressException`

### LLM
- `tests/Feature/OutlineGenerationTest.php`
  - mock de `OpenAiClient`
  - geração de outline com JSON fake válido
  - erro de JSON inválido tratado (status do brief volta para `ready_to_generate`)

## Infra de teste adicionada
- `tests/TestCase.php`
- `tests/CreatesApplication.php`
- Factories:
  - `database/factories/SourceDocumentFactory.php`
  - `database/factories/ContentBriefFactory.php`
  - `database/factories/GeneratedPostFactory.php`

## Como rodar
1. Configurar ambiente de teste (`.env.testing`) com banco dedicado.
2. Executar migrações no banco de teste.
3. Rodar:

```bash
php artisan test
```

Opcional (arquivo específico):

```bash
php artisan test tests/Feature/WordPressClientTest.php
```

## O que está mockado/fake
- OpenAI: mock de `OpenAiClient` com `Mockery`.
- WordPress: `Http::fake()` para simular respostas 201 e 422.
- Chamadas externas reais: **não são executadas**.

## Próximos testes recomendados
- Cobrir jobs (`ChunkDocumentJob`, `GenerateOutlineFromBriefJob`, `SendPostToWordPressJob`) com `Queue::fake()`.
- Cobrir cenários de falha de extração (`DocumentExtractorService`) para PDF/DOCX.
- Adicionar testes de idempotência de jobs em reprocessamento.
- Cobrir fluxo de aprovação antes do envio ao WordPress.
- Cobrir persistência de `WordPressPublication` nos cenários de sucesso e erro.
