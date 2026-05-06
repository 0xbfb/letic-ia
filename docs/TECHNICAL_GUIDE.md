# Guia Técnico da Arquitetura — leticia-seo-mvp

## Status deste documento

Este guia descreve o **estado atual real do repositório** e separa explicitamente:

- **Implementado**
- **Parcialmente implementado**
- **Planejado**
- **Fora do MVP**

---

## 1) Visão geral

### Objetivo do projeto
O projeto é um MVP em Laravel para transformar documentos-base em insumos de conteúdo SEO com apoio de LLM e, ao final, enviar posts para WordPress como rascunho.

### Problema que o MVP resolve
Centralizar ingestão de documentos de referência e preparar base para geração assistida de conteúdo, começando por upload, extração e chunking.

### Fluxo funcional principal (visão alvo)
**Documento → Extração → Chunks → Embeddings → Busca → Briefing → Outline → Artigo → Revisão → WordPress draft**

### Estado atual do fluxo
- **Implementado:** upload de documentos, extração de texto (TXT/PDF/DOCX), chunking, painel Filament para operação manual.
- **Parcialmente implementado:** preparação para embeddings (campo existe em `document_chunks.embedding`, mas geração ainda não existe).
- **Planejado:** busca semântica, briefing, geração de outline/artigo/metadados, auditoria SEO/editorial, publicação WordPress.
- **Fora do MVP atual do repositório:** OCR, publicação final automática em WordPress.

---

## 2) Stack técnica

| Tecnologia | Uso | Arquivos principais | Observações |
|---|---|---|---|
| PHP 8.2 | Runtime da aplicação | `composer.json` | Requisito mínimo definido. |
| Laravel (esperado) | Framework principal | Estrutura `app/`, `database/`, `config/` | O repositório está em formato Laravel modular parcial (sem todos os arquivos base versionados). |
| Filament | Painel administrativo para SourceDocument | `app/Filament/Resources/SourceDocumentResource.php` | Resource operacional já disponível para upload/extração/chunking. |
| PostgreSQL (planejado/compatível) | Banco principal | migrations usam `jsonb` | `jsonb` indica foco em PostgreSQL. |
| pgvector (planejado) | Vetores de embeddings | `document_chunks.embedding` (jsonb por enquanto) | Ainda sem coluna `vector` nativa e sem busca vetorial implementada. |
| Redis (planejado) | Backend de fila/cache | (não encontrado em `config/queue.php` neste recorte) | Necessário para Horizon em ambiente completo. |
| Queue do Laravel | Processamento assíncrono | `app/Jobs/*` | Jobs de extração e chunking implementados. |
| Horizon (planejado) | Observabilidade de filas | (não encontrado neste recorte) | Não há configuração/versionamento visível nesta snapshot. |
| Docker Compose (planejado) | Ambiente local containerizado | `docker-compose.yml` ausente neste recorte | Documentar como planejado no estado atual. |
| OpenAI/LLM provider (planejado) | Geração e embeddings | (não encontrado no código atual) | Sem client/integração nesta versão. |
| WordPress REST API (planejado) | Envio de rascunhos | (não encontrado no código atual) | Sem integração implementada ainda. |
| Storage local | Persistência de arquivos e texto extraído | `SourceDocumentResource`, Jobs | Uso do disco `local` e paths relativos em banco. |
| `smalot/pdfparser` | Extração PDF | `composer.json`, `DocumentExtractorService` | Só funciona bem com PDF textual. |
| `phpoffice/phpword` | Extração DOCX | `composer.json`, `DocumentExtractorService` | Leitura de seções/elementos com `getText`. |

Cuidados de manutenção:
- Tratar PDFs escaneados sem OCR como erro esperado (atual comportamento).
- Manter consistência de status nos jobs.
- Evitar guardar credenciais/sigilos em `metadata` ou logs.

---

## 3) Arquitetura geral

### Diagrama lógico (estado atual + alvo)

Usuário (admin)  
↓  
Filament Resource (SourceDocument)  
↓  
Services de domínio (extração/chunking)  
↓  
Jobs/Queue (ExtractDocumentTextJob, ChunkDocumentJob)  
↓  
PostgreSQL (metadados/chunks) + Storage local (arquivos/texto)  
↓  
[Planejado] LLM Provider / WordPress REST API

### Camadas e responsabilidades
- **Painel administrativo (Filament):** CRUD e ações manuais (extrair texto, gerar chunks, visualizar resultado).
- **Aplicação Laravel:** orquestra regras via jobs e services.
- **Services de domínio:** encapsulam algoritmo de extração/chunking.
- **Jobs/filas:** controlam execução assíncrona, status e logs.
- **Banco:** persiste estado dos documentos e chunks.
- **Storage:** persiste binário original e texto extraído.
- **Integrações externas:** ainda planejadas (LLM/WordPress).
- **Logs/auditoria:** via `Log::info/error` + metadados em JSONB.

---

## 4) Organização de pastas

- `app/Models`: entidades Eloquent (`SourceDocument`, `DocumentChunk`).
  - Deve conter: regras de persistência, casts, relacionamentos.
  - Não deve conter: fluxos longos/orquestração pesada.
- `app/Services`: lógica de domínio reutilizável (`DocumentExtractorService`, `DocumentChunkerService`).
  - Não deve conter: acoplamento de UI.
- `app/Services/Documents` / `Content` / `LLM` / `WordPress`:
  - **Planejado** (ainda não existe essa subdivisão no repositório atual).
- `app/Jobs`: execução assíncrona e transição de status.
- `app/Console/Commands`: **não encontrado neste recorte** (planejado em Laravel completo).
- `app/Filament`: resources/telas administrativas.
- `database/migrations`: estrutura de banco.
- `database/seeders`: **não encontrado neste recorte**.
- `routes`: **não encontrado neste recorte**.
- `config`: config de chunking presente (`config/chunking.php`).
- `docs`: documentação de features e revisões.
- `tests`: **não encontrado neste recorte**.
- `docker`: **não encontrado neste recorte**.

---

## 5) Entidades de domínio

> Abaixo, “implementado” vs “planejado”.

### SourceDocument
- Responsabilidade: representar documento base enviado.
- Tabela: `source_documents` (**implementado**).
- Campos principais: `title`, `description`, `file_path`, `file_type`, `status`, `extracted_text_path`, `metadata`, `created_by`.
- Relacionamentos: `hasMany(DocumentChunk)`.
- Status implementados: `uploaded`, `extracting`, `extracted`, `chunking`, `chunked`, `embedded_pending`, `failed`.
- Criado/atualizado por: Filament Resource e Jobs.

### DocumentChunk
- Responsabilidade: armazenar fragmentos textuais para indexação futura.
- Tabela: `document_chunks` (**implementado**).
- Campos: `source_document_id`, `chunk_index`, `content`, `token_count`, `embedding`, `metadata`.
- Relacionamentos: `belongsTo(SourceDocument)`.

### ContentBrief / GeneratedPost / PostVersion / SeoAudit / LlmRun / WordPressPublication
- **Planejado (não implementado neste repositório atual).**
- Tabelas/modelos/services/jobs associados ainda ausentes.

---

## 6) Fluxos funcionais

## 6.1 Fluxo de documentos (implementado)
Upload → Extração → Chunking → disponível para etapa de embeddings (planejada)

1. **Upload**
   - Entrada: arquivo TXT/PDF/DOCX + título.
   - Processamento: upload no disco local (`source-documents`).
   - Saída: `SourceDocument` status `uploaded`.

2. **Extração**
   - Entrada: `SourceDocument` com `file_path` válido.
   - Service: `DocumentExtractorService`.
   - Job: `ExtractDocumentTextJob`.
   - Saída: arquivo em `extracted-documents/{id}.txt`, status `extracted`.
   - Erros: arquivo ausente, parser PDF/DOCX falho, texto vazio.

3. **Chunking**
   - Entrada: texto extraído.
   - Service: `DocumentChunkerService`.
   - Job: `ChunkDocumentJob`.
   - Saída: registros em `document_chunks`, status `chunked`.
   - Erros: `extracted_text_path` inválido, exceções de processamento.

## 6.2 Fluxo de briefing
- **Planejado.** Ainda sem entidade/service/job/resource.

## 6.3 Fluxo de geração de conteúdo
- **Planejado.** Ainda sem integração LLM.

## 6.4 Fluxo de revisão
- **Planejado.** Ainda sem módulo de checklist/auditoria/versionamento.

## 6.5 Fluxo WordPress
- **Planejado.** Ainda sem client REST e registro de publicação.

---

## 7) Status e máquina de estados

### 7.1 Documentos

| Status | Significado | Pode ir para | Quem altera |
|---|---|---|---|
| uploaded | Documento recém enviado | extracting | criação + action Filament |
| extracting | Extração em andamento | extracted / failed | `ExtractDocumentTextJob` |
| extracted | Texto extraído | chunking | `ExtractDocumentTextJob` |
| chunking | Chunking em andamento | chunked / failed | `ChunkDocumentJob` |
| chunked | Chunks prontos | embedded_pending (planejado) | `ChunkDocumentJob` / futura etapa embeddings |
| embedded_pending | aguardando embeddings | embedding/embedded (planejado) | planejado |
| failed | erro em etapa assíncrona | extracting/chunking (retry manual) | jobs + actions |

### 7.2 Briefing/Post
Todos os status abaixo estão **planejados** no estado atual:
`draft`, `ready_to_generate`, `generating`, `generated`, `needs_review`, `changes_requested`, `approved`, `sent_to_wordpress`, `failed`.

---

## 8) Banco de dados

- Banco-alvo: PostgreSQL (indicado por uso de `jsonb`).
- pgvector: **planejado**; atualmente embeddings estão em `jsonb`.
- Tabelas implementadas:
  - `source_documents`
  - `document_chunks`
- Relacionamentos:
  - `document_chunks.source_document_id` FK com cascade delete.
- JSON/JSONB:
  - `source_documents.metadata`
  - `document_chunks.embedding`
  - `document_chunks.metadata`
- Soft deletes:
  - `source_documents` possui `softDeletes`.
- Índices atuais:
  - `source_documents`: status, file_type, created_by
  - `document_chunks`: unique `(source_document_id, chunk_index)` + índice `source_document_id`

### Regras para migrations
- Preferir migrations incrementais.
- Garantir `migrate` do zero.
- Evitar alterar migration antiga já aplicada.
- Manter compatibilidade PostgreSQL.
- Usar FK quando fizer sentido.
- Usar `jsonb` para metadados flexíveis.
- Quando migrar para pgvector, padronizar dimensão e backfill.

---

## 9) Filas, jobs e processamento assíncrono

Por que usar filas: evitar bloqueio da UI e permitir retries observáveis.

Filas esperadas do produto: `documents`, `embeddings`, `generation`, `wordpress`, `default`.

Estado atual: jobs não definem `$queue` explicitamente (caem na fila padrão configurada no ambiente).

| Job | Fila | Responsabilidade | Entrada | Saída | Falhas comuns |
|---|---|---|---|---|---|
| `ExtractDocumentTextJob` | default (atual) / documents (planejado) | extrair texto do arquivo | `documentId` | texto extraído + status `extracted` | arquivo ausente, PDF/DOCX inválido, texto vazio |
| `ChunkDocumentJob` | default (atual) / documents (planejado) | gerar chunks | `documentId` | `document_chunks` + status `chunked` | texto extraído ausente, erro de escrita/leitura |

Boas práticas:
- Sempre atualizar status antes/depois.
- Em exceção: status `failed`, log estruturado e rethrow quando aplicável.

---

## 10) Services e regras de negócio

### Services/Documents (implementado parcialmente via `app/Services`)
- `DocumentExtractorService`: extração TXT/PDF/DOCX.
- `DocumentChunkerService`: chunking por parágrafo + overlap.
- Embeddings e busca semântica: **planejados**.

### Services/Content (planejado)
Briefing, outline, artigo, metadados, SEO audit, versionamento.

### Services/LLM (planejado)
Client/provedor/prompts centralizados.

### Services/WordPress (planejado)
Client REST + publisher.

Regras arquiteturais:
- Jobs orquestram execução assíncrona.
- Services concentram regra.
- Models sem lógica pesada.
- Resource Filament sem regra complexa de domínio.
- Prompts centralizados (quando módulo LLM existir).

---

## 11) Integração LLM

Estado atual: **planejado** (não implementado no repositório atual).

## Operações LLM (alvo)
- `generate_embedding`
- `generate_outline`
- `generate_article`
- `generate_metadata`
- `audit_editorial`

| Operação | Entrada | Saída esperada | Service | Registro em `llm_runs` |
|---|---|---|---|---|
| generate_embedding | texto de chunk | vetor | planejado | planejado |
| generate_outline | briefing + contexto | outline estruturado | planejado | planejado |
| generate_article | outline + contexto | artigo em seções | planejado | planejado |
| generate_metadata | artigo | title/meta/slug/excerpt | planejado | planejado |
| audit_editorial | artigo + regras | checklist e score | planejado | planejado |

Cuidados:
- Não logar chave de API.
- Validar JSON de resposta.
- Timeout e retry com idempotência.
- Tratar ausência de API key.
- Limitar payload para custo/latência.

---

## 12) Integração WordPress

Estado atual: **planejado**.

Diretriz alvo:
- Endpoint: `/wp-json/wp/v2/posts`
- Auth: Application Password
- Publicar sempre como `draft` no MVP
- Registrar envio em `wordpress_publications` (planejado)

Payload mínimo esperado:

```json
{
  "title": "...",
  "content": "...",
  "status": "draft",
  "slug": "...",
  "excerpt": "..."
}
```

Fora do MVP: publicação final automática sem revisão humana.

---

## 13) Storage e arquivos

Estado atual implementado:
- Originais: `storage/app/source-documents`
- Extraídos: `storage/app/extracted-documents`
- Logs: `storage/logs`

Boas práticas:
- Salvar no banco somente path relativo.
- Garantir permissão de escrita no storage.
- Reprocessamento deve ser seguro (chunking já remove chunks antigos antes de recriar).
- Nunca versionar arquivos de runtime no Git.

---

## 14) Logs e auditoria

Implementado:
- Logs Laravel em jobs (`info/error` com `document_id`, duração, erro).
- Metadados de auditoria em `source_documents.metadata` (`last_extraction`, `last_chunking`, erros).

Planejado:
- tabela `llm_runs`
- tabela `wordpress_publications`

Boas práticas:
- Sempre logar IDs técnicos.
- Incluir duração quando útil.
- Não logar segredos/tokens/payload sensível completo.
- Classificar erro de usuário vs integração vs interno.

---

## 15) Painel administrativo / Filament

| Resource | Entidade | Função | Actions principais |
|---|---|---|---|
| `SourceDocumentResource` | `SourceDocument` | upload/listagem/edição e operação inicial de pipeline | Extrair texto, Gerar chunks, Ver chunks, Ver texto extraído, Baixar/Abrir |

Planejado: resources para briefing, posts gerados, auditoria, publicações WordPress.

---

## 16) Testes

Estado atual: pasta `tests` não encontrada neste recorte.

Comandos recomendados para pipeline de qualidade:
- `composer validate`
- `php artisan test`
- `php artisan route:list`
- em Docker: `docker compose exec app php artisan test`

Recomendações:
- Testar services isolados.
- Testar jobs com Queue fake quando fizer sentido.
- Mockar LLM e WordPress (quando existirem).
- Testar migrate do zero em CI.

---

## 17) Setup local e comandos úteis

Como não há `README.md` nesta snapshot, recomenda-se criar/expandir README em etapa própria.

Comandos essenciais esperados em ambiente Laravel completo:
- `composer install`
- `php artisan key:generate`
- `php artisan migrate`
- `php artisan db:seed`
- `php artisan queue:work`
- `php artisan horizon`
- `php artisan test`
- `docker compose up`

---

## 18) Guia para criar um novo módulo

Exemplo: módulo de sugestão de links internos.

1. Definir escopo e fronteira do módulo.
2. Definir entidade e estado (status).
3. Criar migration incremental.
4. Criar model com casts/relacionamentos.
5. Criar service(s) de domínio.
6. Criar job se assíncrono.
7. Expor em Filament (resource/action).
8. Adicionar logs e trilha de auditoria.
9. Criar testes unitários/integração.
10. Atualizar docs técnicas.
11. Atualizar AGENTS.md (se regras de trabalho mudarem).
12. Atualizar README (se setup/comando mudar).

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

## 19) Guia para manter módulo existente

- Ler docs antes de alterar.
- Mapear fluxo e status impactados.
- Preservar compatibilidade de dados.
- Preferir migration incremental.
- Ajustar testes + validações.
- Atualizar documentação e registrar riscos.

Checklist de manutenção:
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

## 20) Guia para refatorações

Regras:
- Não misturar grande feature com refatoração ampla.
- Refatorar em passos pequenos.
- Preservar comportamento observável.
- Adicionar testes antes, quando possível.
- Documentar motivo da refatoração.
- Evitar abstração prematura.
- Atualizar AGENTS.md quando regra operacional mudar.

Ciclo sugerido de revisão:
- a cada 5 commits de feature
- executar revisão/refatoração geral
- atualizar `docs/REFACTOR_REVIEW.md`
- revisar AGENTS.md

---

## 21) Segurança e segredos

- Nunca commitar `.env` real.
- Nunca commitar tokens/chaves.
- Variáveis sensíveis só em `.env.example` sem valor real.
- Mascarar segredos em logs.
- Cuidado com payload enviado/armazenado de LLM e WordPress.
- Revisão humana obrigatória antes de envio externo.

---

## 22) Limitações conhecidas do MVP

- Sem OCR.
- Sem publicação automática final.
- Sem calendário editorial.
- Sem Search Console.
- Sem SEMrush/Google Trends.
- Sem multiempresa.
- Sem editor avançado dedicado.
- Sem geração de imagens.
- Qualidade depende dos documentos e prompts.
- LLM pode errar; revisão humana é obrigatória.

---

## 23) Roadmap técnico sugerido (não é promessa)

### Curto prazo
- Implementar embeddings + fila dedicada.
- Introduzir busca semântica e seleção de contexto.
- Criar testes de services/jobs atuais.

### Médio prazo
- Módulo de briefing e geração (outline/artigo/metadados).
- Tabela de auditoria LLM (`llm_runs`).
- Integração WordPress draft com rastreabilidade.

### Pós-MVP
- Melhorias editoriais avançadas.
- Observabilidade e métricas de custo LLM.
- Evolução para OCR e enriquecimento externo.

---

## 24) Checklist rápido para PR/commit

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

