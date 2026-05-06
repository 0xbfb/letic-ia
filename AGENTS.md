# AGENTS.md

## Project overview

Este repositório contém o MVP **leticia-seo-mvp**, construído em Laravel, para gerar conteúdo SEO com apoio de LLM a partir de documentos-fonte e enviar o resultado para o WordPress como rascunho.

Objetivo do MVP:
- transformar material de referência em conteúdo editorial SEO com rastreabilidade;
- manter revisão humana como etapa obrigatória antes da publicação externa;
- integrar geração assistida por LLM com fluxo operacional simples e auditável.

Fluxo principal do MVP:
1. Documento
2. Extração de texto
3. Chunking
4. Embeddings
5. Busca semântica
6. Briefing SEO
7. Outline
8. Artigo
9. Metadados SEO
10. Checklist/auditoria
11. Revisão humana
12. Aprovação
13. Envio para WordPress como **draft**

Stack esperada:
- Laravel
- PHP
- Filament
- PostgreSQL com pgvector
- Redis
- Laravel Queue/Horizon
- Docker Compose
- OpenAI (ou provider LLM compatível)
- WordPress REST API

## Development principles

- Trabalhar em incrementos pequenos e verificáveis.
- Preservar a simplicidade do MVP; evitar superengenharia.
- Não criar features fora do escopo pedido.
- Não trocar a stack definida do projeto.
- Evitar abstrações prematuras.
- Preferir código claro a código “esperto”.
- Manter revisão humana obrigatória antes do envio ao WordPress.
- Não fazer publicação automática final no WordPress (somente draft).

## Architecture guidelines

Organização esperada:
- Models em `app/Models`
- Services em `app/Services`
- Jobs em `app/Jobs`
- Filament Resources em `app/Filament`
- Migrations em `database/migrations`
- Seeders em `database/seeders`
- Documentação em `docs`
- Testes em `tests`

Separação de responsabilidades:
- Extração de documentos em `app/Services/Documents`
- Geração de conteúdo em `app/Services/Content`
- Integrações LLM em `app/Services/LLM`
- Integração WordPress em `app/Services/WordPress`
- Jobs devem **orquestrar** etapas, sem concentrar regra de negócio pesada.
- Models devem conter casts, relacionamentos e helpers simples; evitar lógica pesada no Model.

## Domain entities

Entidades principais e responsabilidades:

- `SourceDocument`: documento-fonte, ingestão e estado do pipeline de processamento.
- `DocumentChunk`: fragmentos do documento para recuperação semântica.
- `ContentBrief`: briefing SEO estruturado para orientar geração.
- `GeneratedPost`: conteúdo principal gerado (outline/artigo/metadados/status).
- `PostVersion`: versionamento de alterações editoriais e revisões.
- `SeoAudit`: resultados de checklist/auditoria SEO.
- `LlmRun`: trilha de execução de chamadas de LLM (operação, modelo, duração, status, erro).
- `WordPressPublication`: histórico de envio ao WordPress (request/response/status).

Status de documentos:
- `uploaded`
- `extracting`
- `extracted`
- `chunking`
- `chunked`
- `embedding`
- `embedded`
- `failed`

Status de briefing/post:
- `draft`
- `ready_to_generate`
- `generating`
- `generated`
- `needs_review`
- `changes_requested`
- `approved`
- `sent_to_wordpress`
- `failed`

## Coding standards

- Usar tipagem quando fizer sentido (assinaturas, DTOs, retornos relevantes).
- Usar nomes explícitos e orientados ao domínio.
- Usar casts em Models para `json`/`array`/`datetime`.
- Definir relacionamentos Eloquent de forma clara.
- Preferir Services pequenos, coesos e testáveis.
- Não duplicar prompts nem lógica de integração em múltiplos lugares.
- Tratar exceções com mensagens úteis para operação e debug.
- Incluir IDs relevantes nos logs (ex.: `document_id`, `brief_id`, `post_id`).
- Nunca logar chaves de API, senhas ou tokens.
- Manter compatibilidade com PostgreSQL em queries e migrations.

## Migrations and database

- Todas as migrations devem ser compatíveis com PostgreSQL.
- Usar `json`/`jsonb` quando adequado ao domínio.
- Usar foreign keys quando fizer sentido de integridade.
- Usar soft deletes em entidades editoriais principais quando esperado.
- Cuidar de `pgvector` e da dimensão dos embeddings (consistência entre código e schema).
- Não alterar migrations antigas já aplicadas sem justificativa forte; preferir nova migration.
- Garantir que `migrate` do zero funcione.

## Queues and jobs

- Jobs devem atualizar status inicial e final.
- Jobs devem tratar falhas e marcar `failed` quando aplicável.
- Jobs devem ser razoavelmente idempotentes.
- Usar filas por domínio quando existirem:
  - `documents`
  - `embeddings`
  - `generation`
  - `wordpress`
  - `default`
- Logs de jobs devem incluir `document_id`, `brief_id`, `post_id` ou `operation` quando aplicável.

## LLM integration

- Abstrair provider via `LlmClientInterface` (ou equivalente).
- OpenAI é o provider inicial.
- Prompts devem ter entrada/saída bem definidas.
- Preferir resposta JSON validável para outline, artigo, metadados e auditorias.
- Registrar chamadas em `llm_runs`.
- Não chamar LLM real em testes automatizados.
- Tratar JSON inválido com fallback/erro explícito.
- Tratar ausência de API key de forma clara.
- Registrar duração, modelo, operação, status e erro.
- Evitar enviar conteúdo desnecessário ao provider.

## WordPress integration

- Enviar somente posts com status `approved`.
- Enviar para WordPress como `draft`.
- Não publicar automaticamente.
- Salvar request/response em `WordPressPublication`.
- Tratar erros HTTP com mensagens úteis.
- Não logar credenciais.
- Converter Markdown para HTML antes do envio, se o conteúdo estiver em Markdown.

## Filament/admin panel

- Resources devem ter labels claros para operação.
- Actions devem respeitar status atual da entidade.
- Actions perigosas devem exigir confirmação.
- Tabelas devem ter filtros úteis para operação editorial.
- Badges de status devem ser consistentes no painel.
- Formulários devem ter validação mínima obrigatória.
- UX do MVP deve ser simples, objetiva e utilizável.

## Testing instructions

Comandos esperados (ambiente local):
- `composer validate`
- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan route:list`
- `php artisan test`

Com Docker:
- `docker compose config`
- `docker compose up -d --build`
- `docker compose ps`
- `docker compose exec app composer install`
- `docker compose exec app php artisan migrate`
- `docker compose exec app php artisan test`
- `docker compose exec app php artisan horizon:status`

Regras de teste:
- Usar mocks/fakes para OpenAI e WordPress.
- Não depender de APIs externas reais.
- Se algum comando não puder rodar no contexto da tarefa, documentar como **NÃO TESTADO**.

## Documentation requirements

- Atualizar `README.md` quando setup ou comandos mudarem.
- Criar/atualizar `docs/FEATURE_*.md` para features novas.
- Criar/atualizar `docs/REFACTOR_REVIEW.md` em ciclos de revisão.
- Documentação deve explicitar o que funciona, o que não funciona e limitações conhecidas.
- Não prometer no README algo que o código ainda não faz.

## Security and secrets

- Nunca commitar `.env` real.
- Nunca commitar credenciais.
- Usar `.env.example` para variáveis de ambiente.
- Mascarar tokens em logs.
- Não expor `OPENAI_API_KEY`.
- Não expor `WORDPRESS_APPLICATION_PASSWORD`.
- Evitar salvar payloads sensíveis sem necessidade.

## What not to do

- Não implementar feature fora do pedido.
- Não trocar stack.
- Não adicionar dependência pesada sem necessidade.
- Não criar arquitetura complexa prematura.
- Não fazer publicação automática no WordPress.
- Não criar OCR no MVP sem pedido explícito.
- Não criar multiempresa/SaaS agora.
- Não criar frontend React separado agora.
- Não fazer chamadas reais para APIs externas em testes.
- Não esconder erro; documentar falhas claramente.

## Expected workflow for Codex tasks

1. Ler `AGENTS.md` e `README.md`.
2. Inspecionar código existente antes de editar.
3. Fazer plano curto.
4. Aplicar mudanças pequenas.
5. Rodar validações possíveis.
6. Atualizar docs.
7. Resumir:
   - arquivos alterados
   - comandos executados
   - o que passou
   - o que falhou/não foi testado
   - próximos passos
