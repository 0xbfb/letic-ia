# FEATURE: Briefing Context (pré-visualização de contexto para geração futura)

## Objetivo

Montar um contexto rastreável para uso futuro na geração de outline e artigo, sem gerar conteúdo nesta etapa.

## Como o contexto é montado

1. Recebe um `ContentBrief`.
2. Monta uma query textual com:
   - `main_keyword`
   - `secondary_keywords`
   - `title`
   - `notes`
3. Resolve documentos obrigatórios:
   - prioriza seleção manual via pivot `content_brief_source_document`;
   - fallback para `mandatory_sources` (legado), buscando por título de documento.
4. Executa busca semântica via `DocumentSearchService` com ou sem filtro de documentos.
5. Retorna estrutura de contexto com:
   - query usada;
   - IDs de documentos obrigatórios aplicados;
   - lista de chunks (documento, índice, score/distância, conteúdo).
6. Salva resumo simples do preview em `content_briefs.metadata.preview_context`.

## Como documentos obrigatórios funcionam

- **Com seleção manual no briefing**: busca é limitada aos documentos selecionados.
- **Sem seleção manual e sem legado**: busca ocorre em todos os documentos que já possuem embedding (comportamento herdado da busca semântica).
- **Sem seleção manual, com `mandatory_sources` legado**: tenta mapear tags legadas para títulos de `SourceDocument`.

## Painel (Filament)

`ContentBriefResource` inclui:
- campo de múltipla seleção para documentos obrigatórios (somente documentos `embedded`);
- action `Pré-visualizar contexto`, exibindo:
  - documento de origem;
  - ID de documento;
  - índice do chunk;
  - distância/similaridade;
  - conteúdo do chunk.

## Comando Artisan

Comando opcional criado:

```bash
php artisan briefs:preview-context {brief_id} --limit=8
```

Saída inclui query, documentos obrigatórios aplicados e chunks retornados.

## Como testar

1. Rodar migrations.
2. Garantir documentos com status `embedded` e chunks com embeddings.
3. Criar/editar briefing no Filament e selecionar documentos obrigatórios.
4. Rodar action `Pré-visualizar contexto` e validar lista de chunks.
5. Remover seleção manual e validar busca em todos os documentos embedded.
6. Rodar comando Artisan para o briefing e conferir saída no terminal.
7. Testar briefing sem `main_keyword`/dados de query e validar erro explícito.

## Limitações

- Não gera outline nem artigo (fora do escopo).
- Não executa auditoria SEO.
- Fallback legado por `mandatory_sources` depende de match por título (`ILIKE`) e pode não cobrir referências ambíguas.
- Preview salva apenas resumo em metadata; chunks completos não são persistidos.
