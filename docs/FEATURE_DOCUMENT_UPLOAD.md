# Feature: Upload de Documentos-Base

## Resumo da implementação
Foi implementado o cadastro inicial de `SourceDocument` no painel administrativo (Filament), com upload de arquivos e persistência dos metadados mínimos desta etapa.

Principais pontos:
- Migration para tabela `source_documents` com suporte a PostgreSQL (`jsonb`) e soft deletes.
- Model `SourceDocument` com `SoftDeletes`, cast de `metadata` para `array` e status padrão `uploaded`.
- Resource do Filament para criar/listar/editar documentos.
- Upload salvo em `storage/app/source-documents` (disco `local`).
- Validação para aceitar somente `txt`, `pdf` e `docx`.
- Preenchimento automático de `file_type` e `status` no momento da criação.
- Listagem com colunas: título, tipo, status, data de criação e caminho do arquivo.
- Filtros simples por `status` e `file_type`.
- Ação de tabela para baixar/abrir arquivo quando disponível.

## Arquivos alterados
- `database/migrations/2026_05_05_000000_create_source_documents_table.php`
- `app/Models/SourceDocument.php`
- `app/Filament/Resources/SourceDocumentResource.php`
- `app/Filament/Resources/SourceDocumentResource/Pages/ListSourceDocuments.php`
- `app/Filament/Resources/SourceDocumentResource/Pages/CreateSourceDocument.php`
- `app/Filament/Resources/SourceDocumentResource/Pages/EditSourceDocument.php`
- `docs/FEATURE_DOCUMENT_UPLOAD.md`

## Como testar manualmente
1. Rodar migrations:
   - `php artisan migrate`
2. Acessar o painel Filament.
3. Abrir o resource de documentos-base e clicar em **Criar**.
4. Preencher `Título` e anexar um arquivo `.txt`, `.pdf` ou `.docx`.
5. Salvar.
6. Validar:
   - Registro criado no banco (`source_documents`).
   - `status` salvo como `uploaded`.
   - `file_type` salvo como extensão do arquivo.
   - Arquivo físico em `storage/app/source-documents`.
   - Documento visível na listagem com as colunas esperadas.
7. Tentar upload de extensão inválida (ex.: `.exe`) e confirmar bloqueio de validação.
8. Testar filtros por status e tipo.
9. Testar ação **Baixar/Abrir** na linha do documento.

## Critérios de aceite
- [x] usuário consegue cadastrar um documento
- [x] usuário consegue fazer upload de txt, pdf ou docx
- [x] documento é salvo no storage
- [x] registro é salvo no banco
- [x] status inicial é uploaded
- [x] file_type é preenchido corretamente
- [x] painel lista os documentos
- [x] validação bloqueia extensões inválidas

## Limitações conhecidas
- Não há extração de texto nesta etapa.
- Não há chunking.
- Não há embeddings.
- A ação de abrir/baixar depende da disponibilidade de URL do disco `local` (normalmente requer `storage:link` e configuração adequada de filesystem/servidor).
