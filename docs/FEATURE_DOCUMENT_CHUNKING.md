# Feature: Document Chunking (MVP)

## Estratégia de chunking

- Entrada: texto já extraído em `extracted_text_path`.
- Normalização de quebras de linha para `\n`.
- Separação inicial por parágrafos (blocos separados por linha em branco).
- Agrupamento progressivo de parágrafos até um limite aproximado de caracteres por chunk.
- Overlap simples: ao fechar um chunk, os últimos caracteres do chunk anterior são prefixados no próximo.
- Se o texto for muito pequeno, ainda assim gera pelo menos 1 chunk válido.

## Limites usados

- `target_chars` padrão: **1200** (configurável por `CHUNKING_TARGET_CHARS`).
- `overlap_chars` padrão: **200** (configurável por `CHUNKING_OVERLAP_CHARS`).
- Estimativa inicial de tokens (`token_count`): `ceil(chars / 4)`.

## Fluxo de processamento

1. Validar documento e existência de `extracted_text_path`.
2. Atualizar status para `chunking`.
3. Remover chunks antigos do documento.
4. Gerar novos chunks e salvar com:
   - `chunk_index` sequencial iniciando em `0`.
   - `embedding = null`.
   - `metadata` com dados básicos do chunking.
5. Atualizar status final para `chunked`.
6. Em falha, atualizar status para `failed` e registrar logs.

## Como reprocessar

- No painel Filament (`SourceDocument`), usar a action **Gerar chunks**.
- A action fica disponível para documentos com status `extracted` ou `failed`.
- O reprocessamento sempre apaga os chunks anteriores antes de recriar.

## Como testar

1. Subir migrações (`php artisan migrate`).
2. Garantir um `SourceDocument` em status `extracted` com `extracted_text_path` válido.
3. Acionar **Gerar chunks** no Filament.
4. Verificar:
   - status vai para `chunking` e depois `chunked`;
   - chunks persistidos em `document_chunks`;
   - `chunk_index` sequencial;
   - `embedding` nulo;
   - visualização em **Ver chunks** no painel.
5. Reprocessar e confirmar remoção/recriação dos chunks.
