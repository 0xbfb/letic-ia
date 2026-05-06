# FEATURE: Content Briefs (cadastro e listagem)

## Escopo implementado

Esta etapa implementa apenas o cadastro e a listagem de briefings SEO (`ContentBrief`) no Filament.

Não está implementado nesta etapa:
- geração de outline;
- busca automática de chunks;
- geração de artigo.

## Campos disponíveis

### Obrigatórios
- `title`
- `main_keyword`
- `target_audience`
- `search_intent`
- `business_objective`
- `tone_of_voice`

### Opcionais
- `content_type`
- `secondary_keywords` (array)
- `cta_goal`
- `minimum_words`
- `maximum_words`
- `mandatory_sources` (array)
- `notes`

### Controle
- `status` (default: `draft`)
- `created_by`
- `created_at`
- `updated_at`
- `deleted_at`

## Status possíveis (nesta etapa)
- `draft`
- `ready_to_generate`

## Filtros e actions no Filament

### Filtros
- `status`
- `content_type`

### Actions
- `Marcar como ready_to_generate`
- `Voltar para draft`
- `Editar`

## Como testar

1. Rodar migrations.
2. Acessar o painel Filament e abrir `Content Briefs`.
3. Criar um briefing preenchendo os campos obrigatórios.
4. Validar que o status inicial foi salvo como `draft`.
5. Editar o briefing e salvar palavras-chave secundárias.
6. Confirmar no banco/painel que `secondary_keywords` foi salvo como array.
7. Testar filtro por `status` e por `content_type`.
8. Executar actions para alternar `draft`/`ready_to_generate`.
9. Criar briefing sem preencher obrigatórios e validar bloqueio de validação.
10. Criar briefing sem `mandatory_sources` e validar que não quebra.

## Próximos passos

- Conectar briefing com etapa de geração de outline.
- Definir validações de consistência adicionais (ex.: `minimum_words <= maximum_words`).
- Evoluir catálogo de `content_type` para enum fixa do domínio.
- Incluir testes automatizados (Feature) para CRUD, filtros e transições de status.
