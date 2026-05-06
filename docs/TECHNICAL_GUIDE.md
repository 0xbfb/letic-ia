# Guia Técnico da Arquitetura — leticia-seo-mvp

> Última atualização: 2026-05-06.
>
> Este documento reflete **o estado atual do repositório** e separa claramente:
> - ✅ **Implementado**
> - 🟡 **Parcialmente implementado**
> - 🧭 **Planejado**
> - 🚫 **Fora do MVP**

---

## 1. Visão geral

### Objetivo do projeto
O **leticia-seo-mvp** é um MVP em Laravel para transformar documentos-fonte em insumos de conteúdo SEO com rastreabilidade operacional e revisão humana obrigatória antes do envio ao WordPress.

### Problema que o MVP resolve
- Centraliza ingestão e preparação de conteúdo de referência.
- Reduz trabalho manual de estruturação de contexto para geração editorial.
- Mantém trilha auditável para etapas automatizadas (extração, embeddings, buscas e chamadas LLM).

### Fluxo funcional principal (alvo)
**Documento → Extração → Chunks → Embeddings → Busca → Briefing → Outline → Artigo → Revisão → WordPress draft**

### Estado atual do fluxo
- ✅ Implementado: upload de documentos, extração (TXT/PDF/DOCX), chunking, embeddings por chunk, busca semântica, briefings, geração de outline via LLM, painel Filament para operação.
- 🟡 Parcialmente implementado: pipeline editorial completo após outline (artigo, metadados SEO, auditorias detalhadas, versionamento editorial completo).
- 🟡 Parcialmente implementado: GeneratedPost, PostVersion e SeoAudit já possuem models/migrations/resources, mas o fluxo completo até WordPress ainda está incompleto.
- ✅ Implementado: envio para WordPress como draft com trilha em `WordPressPublication`; ainda existem evoluções planejadas para governança/editorial desse fluxo.
- 🚫 Fora do MVP: OCR, publicação final automática em WordPress (somente draft), multiempresa/SaaS, frontend React separado.

---

## 2. Stack técnica

| Tecnologia | Uso | Arquivos principais | Observações |
|---|---|---|---|
| PHP 8.2 | Runtime da aplicação | `composer.json` | Versão mínima definida no projeto. |
| Laravel (estrutura parcial versionada) | Base de models, jobs, services e migrations | `app/*`, `database/migrations/*` | Nem todos os arquivos-padrão do Laravel estão versionados nesta snapshot (ex.: alguns `config/*.php`). |
| Filament | Painel administrativo de operação do pipeline | `app/Filament/Resources/*` | Resources para `SourceDocument` e `ContentBrief` com actions de pipeline. |
| PostgreSQL | Banco relacional principal | migrations em `database/migrations/*` | Uso consistente de `jsonb` e sintaxe SQL compatível com Postgres. |
| pgvector | Similaridade vetorial para embeddings | migration `2026_05_06_000001_*` e `DocumentSearchService` | `document_chunks.embedding` é convertido para `vector(dim)` via migration. |
| Redis | Backend esperado para filas/cache | (esperado via stack; `config/queue.php` ausente nesta snapshot) | Tratar como dependência de ambiente, especialmente com Horizon. |
| Laravel Queue | Execução assíncrona | `app/Jobs/*` | Filas `embeddings` e `generation` já definidas em jobs; outras ficam no default. |
| Horizon | Observabilidade de filas (esperado) | não versionado nesta snapshot | Planejado na stack, sem configuração visível aqui. |
| Docker Compose | Ambiente local containerizado (esperado) | `docker-compose.yml` ausente nesta snapshot | Deve existir no ambiente completo; aqui tratar como planejado. |
| OpenAI | Provider LLM inicial | `app/Services/LLM/OpenAiClient.php`, `config/llm.php`, `.env.example` | Uso de `/v1/embeddings` e `/v1/responses`. |
| WordPress REST API | Publicação de conteúdo como draft | `app/Services/WordPress/*`, `app/Jobs/SendPostToWordPressJob.php`, `app/Models/WordPressPublication.php` | Implementado com autenticação por Application Password e trilha em `wordpress_publications`. |
| Storage local | Armazenamento de originais e texto extraído | `SourceDocumentResource`, jobs de extração/chunking | Paths persistidos em banco (`file_path`, `extracted_text_path`). |
| `smalot/pdfparser` | Extração de texto de PDF | `composer.json`, `DocumentExtractorService` | Funciona para PDF textual; sem OCR. |
| `phpoffice/phpword` | Extração de texto de DOCX | `composer.json`, `DocumentExtractorService` | Extração por seções/elementos textuais. |

---

## 3. Arquitetura geral

### Diagrama em texto
Usuário (editor/operação)
↓
Filament (Resources e Actions)
↓
Laravel Application Layer
↓
Services de Domínio
↓
Jobs/Queues
↓
PostgreSQL / Storage local / (Redis esperado)
↓
OpenAI (implementado) / WordPress REST API (implementado para draft)

### Responsabilidades por camada
- **Painel administrativo (Filament):** entrada operacional, gatilhos manuais para jobs e visualização de resultados.
- **Aplicação Laravel:** composição de regras e orquestração de fluxos.
- **Services de domínio:** concentram regra de negócio por contexto (documentos, conteúdo, LLM).
- **Jobs:** processamento assíncrono, transições de status e tolerância a falhas.
- **Banco e storage:** persistência de entidades e artefatos de pipeline.
- **Integrações externas:** chamadas LLM e (futuramente) envio para WordPress.
- **Logs/auditoria:** logs estruturados + tabela `llm_runs`.

---

## 4. Organização de pastas

> A estrutura abaixo combina **estado atual** com **organização esperada** do projeto.

- `app/Models`
  - Deve conter: entidades Eloquent, casts e relacionamentos.
  - Não deve conter: orquestração longa de fluxo.
  - Exemplos atuais: `SourceDocument`, `DocumentChunk`, `ContentBrief`, `LlmRun`.

- `app/Services`
  - Deve conter: regras reutilizáveis de domínio.
  - Não deve conter: acoplamento de UI/painel.
  - Exemplo atual legado: `DocumentExtractorService`, `DocumentChunkerService`.

- `app/Services/Documents`
  - Deve conter: embeddings, busca, ingestão técnica.
  - Exemplo atual: `DocumentEmbeddingService`, `DocumentSearchService`.

- `app/Services/Content`
  - Deve conter: briefing, prompt, geração e validação editorial.
  - Exemplo atual: `BriefingBuilderService`, `LlmPromptService`, `OutlineGeneratorService`.

- `app/Services/LLM`
  - Deve conter: contrato e providers.
  - Exemplo atual: `LlmClientInterface`, `OpenAiClient`.

- `app/Services/WordPress`
  - Deve conter: client REST, publisher e tratamento de exceções de integração.
  - Exemplo atual: `WordPressClient`, `WordPressPostPublisher`, `WordPressException`.

- `app/Jobs`
  - Deve conter: orquestração assíncrona e status.
  - Exemplos: `ExtractDocumentTextJob`, `ChunkDocumentJob`, `GenerateDocumentEmbeddingsJob`, `GenerateOutlineFromBriefJob`.

- `app/Console/Commands`
  - Deve conter: comandos operacionais/debug.
  - Exemplos: `documents:search`, `briefs:preview-context`.

- `app/Filament`
  - Deve conter: resources/pages e actions administrativas.
  - Exemplos: `SourceDocumentResource`, `ContentBriefResource`.

- `database/migrations`
  - Deve conter: evolução incremental do schema.
  - Há migrations para `source_documents`, `document_chunks`, `llm_runs`, `content_briefs` e pivot.

- `database/seeders`
  - Estado atual: não versionado nesta snapshot.

- `routes`
  - Estado atual: não versionado nesta snapshot.

- `config`
  - Configs versionadas atualmente: `llm.php`, `chunking.php`.
  - Configs padrão Laravel (`database.php`, `queue.php`, `filesystems.php`) não estão presentes nesta snapshot.

- `docs`
  - Deve conter: documentação de features, revisões e guias técnicos.

- `tests`
  - Estado atual: não versionado nesta snapshot.

- `docker`
  - Estado atual: não versionado nesta snapshot.

---

## 5. Entidades de domínio

### 5.1 SourceDocument
- **Responsabilidade:** documento-fonte e estado do pipeline de ingestão.
- **Tabela:** `source_documents`.
- **Campos principais:** `title`, `description`, `file_path`, `file_type`, `source_type`, `status`, `extracted_text_path`, `metadata`, `created_by`.
- **Relacionamentos:** `hasMany(DocumentChunk)`, `belongsToMany(ContentBrief)`.
- **Status no código:** `uploaded`, `extracting`, `extracted`, `chunking`, `chunked`, `embedding`, `embedded`, `failed`.
- **Quem altera:** Filament Resource e jobs de extração/chunking/embedding.
- **Services/jobs associados:** `DocumentExtractorService`, `DocumentChunkerService`, `DocumentEmbeddingService`, `ExtractDocumentTextJob`, `ChunkDocumentJob`, `GenerateDocumentEmbeddingsJob`.

### 5.2 DocumentChunk
- **Responsabilidade:** fragmentos de texto para recuperação semântica.
- **Tabela:** `document_chunks`.
- **Campos principais:** `source_document_id`, `chunk_index`, `content`, `token_count`, `embedding`, `metadata`.
- **Relacionamentos:** `belongsTo(SourceDocument)`.
- **Status:** não possui status próprio; segue estado do documento pai.
- **Quem altera:** `ChunkDocumentJob`, `DocumentEmbeddingService`.
- **Services/jobs associados:** `DocumentSearchService`, `DocumentEmbeddingService`.

### 5.3 ContentBrief
- **Responsabilidade:** briefing SEO estruturado para orientar geração.
- **Tabela:** `content_briefs`.
- **Campos principais:** `title`, `content_type`, `main_keyword`, `secondary_keywords`, `target_audience`, `search_intent`, `business_objective`, `tone_of_voice`, `cta_goal`, `mandatory_sources`, `metadata`, `status`.
- **Relacionamentos:** `belongsToMany(SourceDocument)` via pivot.
- **Status no código:** `draft`, `ready_to_generate`, `generating`, `generated_outline`.
- **Quem altera:** `ContentBriefResource`, `GenerateOutlineFromBriefJob`, `OutlineGeneratorService`.
- **Services/jobs associados:** `BriefingBuilderService`, `LlmPromptService`, `OutlineGeneratorService`.

### 5.4 GeneratedPost
- **Responsabilidade:** conteúdo gerado (artigo, metadados, score e status editorial).
- **Tabela:** `generated_posts`.
- **Campos principais:** `content_brief_id`, `title`, `slug`, `meta_title`, `meta_description`, `excerpt`, `content`, `faq_json`, `cta_json`, `status`, `seo_score`, `readability_score`, `tone_score`, `approved_by`.
- **Relacionamentos:** `belongsTo(ContentBrief)`, `hasMany(PostVersion)`, `hasMany(SeoAudit)`.
- **Status no código:** `needs_review` (implementado no model); demais estados editoriais são parcialmente tratados no painel/fluxos.
- **Quem altera:** `GeneratedPostResource`, `PostVersionService`, `SeoAuditService`, `EditorialAuditService`, `MetadataGeneratorService`.

### 5.5 PostVersion
- **Responsabilidade:** versionamento de edições de `GeneratedPost`.
- **Tabela:** `post_versions`.
- **Campos principais:** `generated_post_id`, `version_number`, `title`, `content`, `meta_title`, `meta_description`, `change_summary`, `created_by`, `created_at`.
- **Relacionamentos:** `belongsTo(GeneratedPost)`.
- **Quem altera:** `PostVersionService` e ações de `GeneratedPostResource`.

### 5.6 SeoAudit
- **Responsabilidade:** checklist/auditoria SEO/editorial por post gerado.
- **Tabela:** `seo_audits`.
- **Campos principais:** `generated_post_id`, `audit_type`, `score`, `checks_json`, `warnings_json`, `errors_json`.
- **Relacionamentos:** `belongsTo(GeneratedPost)`.
- **Quem altera:** `SeoAuditService` e `EditorialAuditService`.
- **Nota de estado:** tabela/model implementados; evolução de regras e cobertura de fluxo ainda parcial.

### 5.7 LlmRun
- **Responsabilidade:** auditoria de chamadas LLM (operação/modelo/duração/status/erro/uso).
- **Tabela:** `llm_runs`.
- **Campos principais:** `provider`, `model`, `operation`, `related_type`, `related_id`, `status`, `error`, `duration_ms`, `prompt_tokens`, `completion_tokens`, `total_tokens`, `metadata`.
- **Quem cria:** `DocumentEmbeddingService`, `DocumentSearchService`, `OutlineGeneratorService`.

### 5.8 WordPressPublication
- **Responsabilidade:** histórico de envio WordPress.
- **Estado:** ✅ implementado (model, migration, job e resource de visualização).

---

## 6. Fluxos funcionais

## 6.1 Fluxo de documentos
**Upload → extração → chunking → embeddings → documento disponível para busca**

1. **Upload**
   - Entrada: arquivo (TXT/PDF/DOCX) e metadados básicos.
   - Processamento: gravação no disco local (`source-documents`).
   - Saída: `SourceDocument` com status `uploaded`.

2. **Extração**
   - Entrada: `SourceDocument.file_path`.
   - Service: `DocumentExtractorService`.
   - Job: `ExtractDocumentTextJob`.
   - Saída: `extracted-documents/{id}.txt`, status `extracted`.
   - Erros comuns: arquivo ausente, formato não suportado, texto vazio.

3. **Chunking**
   - Entrada: texto extraído.
   - Service: `DocumentChunkerService`.
   - Job: `ChunkDocumentJob`.
   - Saída: `document_chunks` preenchidos, status `chunked`.
   - Erros comuns: `extracted_text_path` inválido, falha de parsing/cálculo.

4. **Embeddings**
   - Entrada: chunks sem embedding.
   - Service: `DocumentEmbeddingService`.
   - Job: `GenerateDocumentEmbeddingsJob`.
   - Saída: embeddings gerados e status `embedded` quando completo.
   - Erros comuns: API key ausente, timeout/rede OpenAI, dimensão inválida.

## 6.2 Fluxo de briefing
**Criação do briefing → seleção de documentos → busca de contexto → pronto para geração**
- Implementação atual:
  - `ContentBriefResource` permite associar documentos e status.
  - `BriefingBuilderService` monta query e contexto por busca semântica.
  - `PreviewBriefContextCommand` e action “Pré-visualizar contexto” ajudam operação.

## 6.3 Fluxo de geração de conteúdo
**Briefing → contexto → outline → (artigo/metadados planejados) → versão inicial**
- Implementado hoje:
  - geração de **outline** via `GenerateOutlineFromBriefJob` + `OutlineGeneratorService`.
  - persistência do outline em `content_briefs.metadata.outline`.
- Parcial/planejado:
  - geração de artigo completo, metadados SEO e versão editorial.

## 6.4 Fluxo de revisão
**Post gerado → checklist/auditoria → edição manual → versão → aprovação**
- Estado: 🟡 parcial.
- Existe revisão operacional manual de briefing/outline no painel.
- Módulos de `SeoAudit` e `PostVersion` já existem; a governança completa de aprovação/publicação ainda está em evolução.

## 6.5 Fluxo WordPress
**Post aprovado → conversão para HTML → envio REST API → draft → registro**
- Estado: 🧭 planejado.
- Não há serviço/job/model de publicação implementado nesta snapshot.

---

## 7. Status e máquina de estados

### 7.1 Status de documentos

| Status | Significado | Pode ir para | Quem altera |
|---|---|---|---|
| uploaded | Documento recém criado | extracting, failed | criação/ações do painel |
| extracting | Extração em andamento | extracted, failed | `ExtractDocumentTextJob` |
| extracted | Texto extraído com sucesso | chunking, failed | `ExtractDocumentTextJob` + ações |
| chunking | Chunking em andamento | chunked, failed | `ChunkDocumentJob` |
| chunked | Chunks prontos | embedding, failed | `ChunkDocumentJob` + ações |
| embedding | Geração de embeddings em execução | embedded, failed | `GenerateDocumentEmbeddingsJob` |
| embedded | Embeddings finalizados | (consumo por busca/briefing) | `GenerateDocumentEmbeddingsJob` |
| failed | Falha operacional | extracting/chunking/embedding (retry manual) | jobs/actions |

### 7.2 Status de briefing/post

| Status | Significado | Pode ir para | Quem altera |
|---|---|---|---|
| draft | Briefing em construção | ready_to_generate | Resource/action |
| ready_to_generate | Pronto para job de outline | generating, draft | Resource/action |
| generating | Job de geração em execução | generated_outline, ready_to_generate | job/service |
| generated_outline | Outline salvo em metadata | draft (ajustes), próximos estados planejados | service/action |
| generated | 🧭 planejado (estado pós-artigo completo) | needs_review | planejado |
| needs_review | 🧭 planejado | changes_requested, approved | planejado |
| changes_requested | 🧭 planejado | generating/needs_review | planejado |
| approved | 🧭 planejado | sent_to_wordpress | planejado |
| sent_to_wordpress | 🧭 planejado | — | planejado |
| failed | Falha de pipeline editorial | ready_to_generate/draft | job/service |

---

## 8. Banco de dados

- **Banco usado:** PostgreSQL (migrations com `jsonb` e SQL `vector`).
- **Motivo:** suporte robusto a JSONB, integridade relacional e extensão vetorial.
- **pgvector:** habilitado por migration (`CREATE EXTENSION IF NOT EXISTS vector`) e cast da coluna `embedding` para `vector(dim)`.
- **Tabelas principais hoje:** `source_documents`, `document_chunks`, `llm_runs`, `content_briefs`, `content_brief_source_document`.
- **Relacionamentos principais:**
  - `source_documents` 1:N `document_chunks`
  - `content_briefs` N:N `source_documents`
- **JSON/JSONB:** `metadata` em múltiplas entidades; `secondary_keywords` e `mandatory_sources` em briefing.
- **Soft deletes:** `source_documents` e `content_briefs`.
- **Índices relevantes:** status/tipos em documentos e briefs; índices por operação/status em `llm_runs`; unique de pivot.

### Regras para migrations
- Preferir migrations incrementais.
- Manter compatibilidade PostgreSQL.
- Garantir `migrate` do zero em ambiente limpo.
- Não alterar migration antiga já aplicada sem justificativa forte.
- Usar foreign keys quando fizer sentido.
- Usar `jsonb` para estruturas flexíveis.
- Cuidar da dimensão dos embeddings para manter consistência com `OPENAI_EMBEDDING_DIMENSIONS`.

---

## 9. Filas, jobs e processamento assíncrono

### Por que usar filas
- Evitar travar requisições do painel em operações pesadas.
- Permitir retry e observabilidade.
- Isolar falhas de integração externa.

### Filas esperadas
- `documents`
- `embeddings`
- `generation`
- `wordpress`
- `default`

### Estado atual
- `GenerateDocumentEmbeddingsJob` usa fila `embeddings`.
- `GenerateOutlineFromBriefJob` usa fila `generation`.
- `ExtractDocumentTextJob` e `ChunkDocumentJob` não definem fila explicitamente (caem na default).

| Job | Fila | Responsabilidade | Entrada | Saída | Falhas comuns |
|---|---|---|---|---|---|
| `ExtractDocumentTextJob` | default | Extrair texto do documento | `documentId` | `extracted_text_path` + status `extracted` | Arquivo ausente, parser falho, texto vazio |
| `ChunkDocumentJob` | default | Gerar chunks | `documentId` | registros em `document_chunks` + status `chunked` | Texto não encontrado, erro de chunking |
| `GenerateDocumentEmbeddingsJob` | embeddings | Gerar embeddings dos chunks | `documentId` | embeddings salvos + status `embedded` | API key ausente, falha de rede/timeout |
| `GenerateOutlineFromBriefJob` | generation | Gerar outline via LLM | `briefId` | `metadata.outline` + status `generated_outline` | JSON inválido do provider, falha OpenAI |

Boas práticas:
- Jobs devem atualizar status inicial/final.
- Em falha, marcar estado consistente (`failed` quando aplicável).
- Logar IDs de domínio e duração quando possível.

---

## 10. Services e regras de negócio

## Services/Documents
- `DocumentEmbeddingService`: gera e persiste embeddings por chunk; registra `llm_runs`.
- `DocumentSearchService`: busca semântica com operador `<=>` do pgvector.

## Services/Content
- `BriefingBuilderService`: monta query de contexto e resolve documentos obrigatórios.
- `LlmPromptService`: centraliza prompt estruturado para outline.
- `OutlineGeneratorService`: executa chamada LLM, valida JSON, persiste resultado e auditoria.

## Services/LLM
- `LlmClientInterface`: contrato para embeddings/texto.
- `OpenAiClient`: implementação atual (OpenAI).

## Services/WordPress
- Estado: 🧭 planejado.

### Regras arquiteturais
- Jobs **orquestram** etapas assíncronas.
- Services concentram regra de negócio.
- Models evitam lógica pesada.
- Resources/Controllers evitam regra complexa.
- Prompts devem ficar centralizados (evitar duplicação).

---

## 11. Integração LLM

- **Provider inicial:** OpenAI.
- **Variáveis de ambiente atuais:** `LLM_PROVIDER`, `OPENAI_API_KEY`, `OPENAI_EMBEDDING_MODEL`, `OPENAI_EMBEDDING_DIMENSIONS`.
- **Modelo chat padrão:** `gpt-4.1-mini` (config `llm.php`).
- **Formato de prompt:** mensagens com papéis `system`/`user`, retorno esperado em JSON para outline.
- **Registro de execução:** tabela `llm_runs`.
- **Tratamento de erro:** validação de chave ausente, HTTP >=400, resposta vazia, JSON inválido.

## Operações LLM

| Operação | Entrada | Saída esperada | Service | Registro em `llm_runs` |
|---|---|---|---|---|
| `generate_embedding` | texto de chunk | vetor com dimensão configurada | `DocumentEmbeddingService` | success/failed + tokens/duração |
| `search_query_embedding` | texto da consulta | embedding da consulta + resultados por distância | `DocumentSearchService` | success/failed + metadados de busca |
| `generate_outline` | briefing + chunks relevantes | JSON de outline válido | `OutlineGeneratorService` | success/failed + prompt/response |
| `generate_article` | 🧭 planejado | artigo estruturado | planejado | planejado |
| `generate_metadata` | 🧭 planejado | metadados SEO | planejado | planejado |
| `audit_editorial` | 🧭 planejado | auditoria/checklist | planejado | planejado |

Cuidados:
- Não enviar dados desnecessários ao provider.
- Nunca logar API keys.
- Lidar com JSON inválido explicitamente.
- Tratar timeout/rede com mensagens operacionais úteis.
- Falhar de forma clara quando `OPENAI_API_KEY` estiver ausente.

---

## 12. Integração WordPress

### Estado atual
✅ Implementado no código atual com `WordPressClient`, `WordPressPostPublisher` e `SendPostToWordPressJob`, condicionado a post aprovado.

### Contrato esperado (MVP)
- Endpoint REST típico: `/wp-json/wp/v2/posts`.
- Autenticação esperada: Application Password.
- Status de publicação: sempre `draft`.
- Persistência de trilha: `wordpress_publications` (implementado).

### Exemplo de payload esperado
```json
{
  "title": "...",
  "content": "...",
  "status": "draft",
  "slug": "...",
  "excerpt": "..."
}
```

**Importante:** publicação final automática está fora do MVP.

---

## 13. Storage e arquivos

### Estado atual
- Arquivos originais: `storage/app/source-documents`.
- Textos extraídos: `storage/app/extracted-documents`.
- Logs: `storage/logs`.

### Regras
- Persistir paths relativos no banco (`file_path`, `extracted_text_path`).
- Garantir permissões corretas do storage no ambiente local/container.
- Não versionar arquivos gerados de runtime.
- Planejar rotinas de limpeza/reprocessamento sem perder rastreabilidade.

---

## 14. Logs e auditoria

### Fontes atuais
- Logs Laravel (`Log::info`, `Log::error`, `Log::warning`) nos jobs/services.
- Tabela `llm_runs` para chamadas LLM.

### Fontes planejadas
- `wordpress_publications` para trilha de envio ao WordPress.

### Boas práticas
- Sempre logar IDs (`document_id`, `brief_id`, `chunk_id`).
- Registrar duração quando útil.
- Não logar segredos/credenciais.
- Salvar mensagens de erro acionáveis para debug.
- Diferenciar erro de usuário/dados, erro de integração e erro interno.

---

## 15. Painel administrativo / Filament

### Resources existentes

| Resource | Entidade | Função | Actions principais |
|---|---|---|---|
| `SourceDocumentResource` | `SourceDocument` | Operar ingestão técnica | Extrair texto, gerar chunks, gerar embeddings, visualizar chunks/texto, preview busca semântica |
| `ContentBriefResource` | `ContentBrief` | Operar briefing e outline | Pré-visualizar contexto, gerar outline, ver outline, transições de status |
| `GeneratedPostResource` | `GeneratedPost` | Revisão editorial e versionamento | gerar artigo/metadados, auditoria SEO/editorial, aprovar, criar versão |

### Recursos planejados
- Resource específico para `WordPressPublication` e ações de envio/reenvio dedicadas.

### Diretrizes
- Actions devem respeitar status atual.
- Actions sensíveis devem pedir confirmação.
- Tabelas com filtros por status/tipo.
- Badges de status consistentes.

---

## 16. Testes

### Estratégia
- Priorizar testes de services e jobs por módulo.
- Mockar/fakear chamadas LLM e WordPress.
- Não chamar APIs externas reais nos testes automatizados.

### Comandos recomendados
- `composer validate`
- `php artisan route:list`
- `php artisan test`
- Com Docker: `docker compose exec app php artisan test`

### Estado atual desta snapshot
- Pasta `tests` não está versionada aqui.
- Ainda assim, recomenda-se ampliar cobertura para:
  - extração/chunking
  - embeddings/search
  - geração de outline
  - transições de status

---

## 17. Setup local e comandos úteis

Como referência principal, usar a documentação operacional do projeto (quando `README.md` estiver disponível no repositório completo).

Comandos essenciais esperados:
- `docker compose up -d --build`
- `composer install`
- `php artisan key:generate`
- `php artisan migrate`
- `php artisan db:seed`
- `php artisan horizon`
- `php artisan queue:work`
- `php artisan test`

Comandos de debug já implementados:
- `php artisan documents:search "consulta" --limit=8`
- `php artisan briefs:preview-context 1 --limit=8`

---

## 18. Guia para criar um novo módulo

Exemplo: **módulo de sugestão de links internos**.

1. Definir escopo (entrada, saída, limites do MVP).
2. Criar/revisar entidade e tabela.
3. Criar migration incremental.
4. Criar model com casts/relacionamentos.
5. Criar service de domínio.
6. Criar job se assíncrono.
7. Criar Resource/action no Filament.
8. Adicionar logs/auditoria.
9. Adicionar testes.
10. Atualizar docs técnicas.
11. Atualizar AGENTS.md se regra de trabalho mudar.
12. Atualizar README se setup/comandos mudarem.

Checklist:
- [ ] escopo documentado
- [ ] migration criada
- [ ] model com casts/relacionamentos
- [ ] service criado
- [ ] job criado, se necessário
- [ ] resource/action criado
- [ ] logs adicionados
- [ ] testes adicionados
- [ ] docs atualizadas
- [ ] nenhuma credencial exposta
- [ ] comandos de validação executados

---

## 19. Guia para manter um módulo existente

1. Ler documentação e código atual antes de editar.
2. Identificar fluxo afetado e estados envolvidos.
3. Preservar compatibilidade de dados.
4. Usar migration incremental (evitar editar migration antiga).
5. Ajustar jobs/services mantendo idempotência.
6. Atualizar actions/fluxo de painel se necessário.
7. Atualizar testes.
8. Rodar validações.
9. Atualizar documentação.
10. Registrar riscos/rollback.

Checklist:
- [ ] comportamento atual entendido
- [ ] impacto mapeado
- [ ] migrations seguras
- [ ] status preservados ou migrados
- [ ] jobs ajustados
- [ ] actions ajustadas
- [ ] testes atualizados
- [ ] docs atualizadas
- [ ] rollback considerado

---

## 20. Guia para refatorações

Regras:
- Não misturar refatoração grande com feature nova.
- Refatorar em passos pequenos e verificáveis.
- Preservar comportamento funcional.
- Adicionar testes antes, quando possível.
- Documentar motivação e ganho.
- Evitar abstração prematura.
- Atualizar AGENTS.md quando diretrizes mudarem.

Ciclo sugerido de revisão:
- A cada 5 commits de feature, executar revisão/refatoração geral.
- Atualizar `docs/REFACTOR_REVIEW.md`.
- Revisar aderência ao `AGENTS.md`.

---

## 21. Segurança e segredos

- Nunca commitar `.env` real.
- Nunca commitar tokens/credenciais.
- Manter variáveis no `.env.example` sem valores sensíveis.
- Mascarar segredos em logs.
- Revisar payloads enviados a LLM para evitar dados desnecessários/sensíveis.
- Revisar payloads de integração WordPress para evitar exposição indevida.
- Revisão humana obrigatória antes do envio externo.

---

## 22. Limitações conhecidas do MVP

- Sem OCR.
- Sem publicação automática final no WordPress.
- Sem calendário editorial.
- Sem integração Search Console.
- Sem integração SEMrush/Google Trends.
- Sem multiempresa/SaaS.
- Sem editor avançado dedicado.
- Sem geração de imagens.
- Qualidade depende da qualidade dos documentos e prompts.
- LLM pode errar: revisão humana continua obrigatória.

---

## 23. Roadmap técnico sugerido (não-promessa)

### Curto prazo
- Evoluir fluxo de `GeneratedPost` para cobrir cenários editoriais extremos.
- Refinar regras e cobertura de `SeoAudit` (score/checks e ações corretivas).
- Completar estratégia de testes automatizados.

### Médio prazo
- Evoluir observabilidade de filas (Horizon + métricas de erro).
- Consolidação de políticas de retry e idempotência.

### Pós-MVP
- Melhorias avançadas de QA editorial.
- Integrações SEO adicionais (quando houver escopo).
- Otimizações de custo/performance de prompts e embeddings.

---

## 24. Checklist rápido para PR/commit

- [ ] escopo pequeno
- [ ] código revisado
- [ ] migrations testadas
- [ ] jobs/status verificados
- [ ] logs úteis
- [ ] testes executados
- [ ] docs atualizadas
- [ ] AGENTS.md atualizado se necessário
- [ ] sem segredos
- [ ] sem feature fora do pedido

---

## Apêndice — diferenças importantes entre “esperado” e “atual”

1. `README.md` não está presente nesta snapshot local; portanto não foi possível adicionar link para este guia nesta tarefa.
2. `docker-compose.yml` não está presente nesta snapshot local (somente referência de stack esperada).
3. `config/database.php`, `config/queue.php` e `config/filesystems.php` não estão presentes nesta snapshot local.
4. O repositório já implementa partes editoriais além do básico: `GeneratedPost`, `PostVersion`, `SeoAudit` e também integração WordPress draft com `WordPressPublication` (model, migration, services, job e resource).
