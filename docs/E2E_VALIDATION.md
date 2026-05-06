# E2E Validation — leticia-seo-mvp

## Objetivo
Validar manualmente o fluxo ponta a ponta do MVP:

Documento → extração → chunking → embeddings → briefing → contexto → outline → artigo → metadados → checklist SEO → auditoria editorial → revisão/edição → aprovação → envio WordPress (draft).

> Escopo desta validação: **não criar novas features**. Apenas executar validações, evidenciar resultados e corrigir impedimentos pequenos.

---

## 1) Pré-requisitos

### Infra
- PHP 8.2+ e Composer 2+.
- PostgreSQL 15+ com extensão `vector`.
- Redis disponível.
- Worker de fila ativo (`documents,embeddings,generation,wordpress,default`).
- (Opcional) Horizon ativo para observabilidade.

### Aplicação
- Dependências instaladas (`composer install`).
- Banco migrado (`php artisan migrate`).
- Seed opcional (`php artisan db:seed`) para dados de referência.
- Painel Filament acessível para acionar as etapas manuais.

### WordPress
- Ambiente WordPress com REST API acessível **ou** mock/fake HTTP para validação controlada.
- Usuário com Application Password válido.

---

## 2) Variáveis de ambiente necessárias

### App e filas
- `APP_ENV`, `APP_KEY`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `REDIS_HOST`, `REDIS_PORT`
- `QUEUE_CONNECTION`

### LLM
- `OPENAI_API_KEY`
- `OPENAI_MODEL`

### WordPress
- `WORDPRESS_BASE_URL`
- `WORDPRESS_USERNAME`
- `WORDPRESS_APPLICATION_PASSWORD`

> Segurança: nunca logar nem versionar credenciais reais.

---

## 3) Comandos-base de validação

## 3.1 Esperados no ambiente Laravel completo
```bash
composer validate
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan test
```

## 3.2 Docker (quando aplicável)
```bash
docker compose config
docker compose up -d --build
docker compose ps
docker compose exec app composer install
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app php artisan horizon:status
```

---

## 4) Roteiro manual E2E (passo a passo)

> O acionamento abaixo assume uso do painel Filament e actions já implementadas nos Resources.

### Etapa 1 — Cadastrar documento
1. Abrir `SourceDocumentResource`.
2. Criar documento (`uploaded`) com arquivo válido (txt/pdf/docx suportado pelo fluxo atual).

**Resultado esperado**
- Registro em `source_documents` com `status=uploaded`.
- `file_path` preenchido.

**Query de conferência**
```sql
select id, title, status, file_path, created_at
from source_documents
order by id desc
limit 10;
```

### Etapa 2 — Extração de texto
1. Acionar action **Extrair texto** no documento.
2. Aguardar job/fila.

**Resultado esperado**
- Transição `uploaded/extracting` → `extracted`.
- `extracted_text_path` preenchido.

**Query**
```sql
select id, status, extracted_text_path, updated_at
from source_documents
where id = :document_id;
```

### Etapa 3 — Chunking
1. Acionar action **Gerar chunks**.

**Resultado esperado**
- Transição para `chunked`.
- Registros em `document_chunks`.

**Queries**
```sql
select id, status, updated_at
from source_documents
where id = :document_id;

select source_document_id, count(*) as chunks
from document_chunks
where source_document_id = :document_id
group by source_document_id;
```

### Etapa 4 — Embeddings
1. Acionar action **Gerar embeddings**.

**Resultado esperado**
- Transição para `embedded`.
- Chunks com vetor preenchido.
- Registros em `llm_runs` (`operation=generate_embedding`).

**Queries**
```sql
select id, status, updated_at
from source_documents
where id = :document_id;

select count(*) as chunks_total,
       count(*) filter (where embedding is not null) as chunks_com_embedding
from document_chunks
where source_document_id = :document_id;
```

### Etapa 5 — Briefing
1. Criar/editar `ContentBrief` e associar documentos.
2. Colocar em status operacional para geração (`ready_to_generate`).

**Resultado esperado**
- `content_briefs` com vínculo no pivot e status correto.

**Queries**
```sql
select id, title, status, created_at
from content_briefs
order by id desc
limit 10;

select content_brief_id, source_document_id
from content_brief_source_document
where content_brief_id = :brief_id;
```

### Etapa 6 — Contexto semântico
1. Acionar preview/ação de contexto semântico do briefing.

**Resultado esperado**
- Recuperação de chunks relevantes.
- `llm_runs` (quando a etapa envolver chamada LLM) com status auditável.

### Etapa 7 — Outline
1. Acionar **Gerar outline** no `ContentBrief`.

**Resultado esperado**
- Outline persistido no briefing.
- `llm_runs.operation=generate_outline` com `status=success`.

**Query**
```sql
select id, status, outline_json, updated_at
from content_briefs
where id = :brief_id;
```

### Etapa 8 — Artigo
1. Acionar **Gerar artigo**.

**Resultado esperado**
- Criação de `generated_posts`.
- `generated_posts.status=needs_review` (revisão humana obrigatória).
- `llm_runs.operation=generate_article`.

**Query**
```sql
select id, content_brief_id, status, created_at
from generated_posts
where content_brief_id = :brief_id
order by id desc;
```

### Etapa 9 — Metadados SEO
1. Acionar **Gerar metadados SEO** no post.

**Resultado esperado**
- Campos de metadados preenchidos no post.
- `llm_runs.operation=generate_metadata` com sucesso.

### Etapa 10 — Checklist SEO
1. Acionar action de checklist SEO.

**Resultado esperado**
- Criação de registro em `seo_audits` com score/checklist.
- `llm_runs` correspondente (se LLM for usado na implementação ativa).

### Etapa 11 — Auditoria editorial
1. Acionar auditoria editorial.

**Resultado esperado**
- Resultado salvo em `seo_audits`/campos editoriais conforme implementação.
- `llm_runs.operation=audit_editorial`.

### Etapa 12 — Revisão e edição manual
1. Editar conteúdo no `GeneratedPostResource`.
2. Salvar ajustes.

**Resultado esperado**
- Alterações persistidas.
- Histórico de versão (`post_versions`) incrementado quando houver mudança relevante.

### Etapa 13 — Aprovação
1. Executar ação de **aprovar**.

**Resultado esperado**
- `generated_posts.status=approved`.

### Etapa 14 — Envio para WordPress (draft)
1. Acionar **Enviar para WordPress** no post aprovado.

**Resultado esperado**
- Só permite envio com status `approved`.
- Cria `wordpress_publications` com request/response/status.
- WordPress recebe post com `status=draft`.
- Post local atualizado para `sent_to_wordpress` em sucesso.

**Queries**
```sql
select id, generated_post_id, status, wordpress_post_id, created_at
from wordpress_publications
where generated_post_id = :post_id
order by id desc;

select id, status, wordpress_post_id, updated_at
from generated_posts
where id = :post_id;
```

---

## 5) Queries úteis para auditoria de logs (`llm_runs`)

```sql
-- Últimas execuções
select id, operation, status, related_type, related_id, model, duration_ms, created_at
from llm_runs
order by id desc
limit 50;

-- Falhas por operação
select operation, count(*) as total_falhas
from llm_runs
where status = 'failed'
group by operation
order by total_falhas desc;

-- Tempo médio por operação
select operation,
       round(avg(duration_ms)::numeric, 2) as avg_duration_ms,
       count(*) as total
from llm_runs
group by operation
order by operation;
```

---

## 6) Resultado desta execução no ambiente atual

### Execuções realizadas
- `composer validate` → executado.
- Tentativa de comandos Laravel (`php artisan ...`) bloqueada por ausência de arquivo `artisan` no repositório atual.

### Evidência objetiva
- `composer validate`: passou com warning de license ausente no `composer.json`.
- `php artisan config:clear`: falhou com `Could not open input file: artisan`.

### Impacto
Sem entrypoint `artisan`, **não foi possível** executar a validação E2E real do pipeline (jobs, filas, resources, estados de banco) neste ambiente.

---

## 7) Problemas encontrados

1. **Bloqueador de execução**: arquivo `artisan` inexistente no checkout atual.
2. Com isso, não foi possível rodar comandos obrigatórios de validação (`route:list`, `test`, `migrate`, `horizon:status`) nem operar fluxo end-to-end pela aplicação.

---

## 8) Correções aplicadas

- Nenhuma correção de código funcional foi aplicada (não havia como validar execução de runtime Laravel sem `artisan`).
- Foi criada esta documentação operacional E2E para execução imediata assim que o ambiente estiver completo.

---

## 9) Pendências

1. Restaurar checkout completo Laravel (incluindo `artisan` e bootstrap padrão).
2. Subir infraestrutura (Postgres+pgvector, Redis, queue workers).
3. Reexecutar roteiro completo e anexar evidências por etapa (IDs, timestamps, status finais).
4. Validar envio WordPress em dois cenários:
   - mock/fake HTTP (teste controlado);
   - ambiente real (smoke test de integração, mantendo `draft`).

---

## 10) Checklist de aceite (para marcar após reexecução com ambiente completo)

- [ ] documento é cadastrado
- [ ] texto é extraído
- [ ] chunks são criados
- [ ] embeddings são gerados
- [ ] briefing é criado
- [ ] contexto é encontrado
- [ ] outline é gerado
- [ ] artigo é gerado
- [ ] metadados são gerados
- [ ] checklist SEO roda
- [ ] auditoria editorial roda
- [ ] post pode ser editado
- [ ] post pode ser aprovado
- [ ] post aprovado pode ser enviado ao WordPress como draft
- [ ] logs permitem auditar o fluxo
