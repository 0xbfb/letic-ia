# FEATURE: REVIEW PANEL (GeneratedPost)

## Organização da tela

A edição/revisão de `GeneratedPost` foi reorganizada em seções para reduzir rolagem confusa e deixar o fluxo de revisão mais claro:

1. **Dados principais**
   - `title`, `slug`, `status`, `excerpt`
2. **SEO**
   - `meta_title`, `meta_description`
   - score SEO e scores editoriais (`seo_score`, `tone_score`, `readability_score`)
3. **Conteúdo**
   - editor Markdown para `content`
4. **FAQ**
   - `faq_json`
5. **CTAs**
   - `cta_json`
6. **Auditorias**
   - última auditoria SEO (checks, warnings, errors)
   - última auditoria editorial (checks, warnings, errors)
7. **Versões**
   - indicação de que o histórico detalhado está no relacionamento de versões
8. **Logs LLM relacionados**
   - listagem textual dos últimos `llm_runs` relacionados ao post e ao briefing

## Ações disponíveis

Ações principais adicionadas/garantidas na revisão:

- **Rodar checklist SEO**
- **Rodar auditoria editorial**
- **Gerar metadados**
- **Aprovar** (seta status para `approved`)
- **Solicitar ajustes** (seta status para `changes_requested`)

As ações aparecem:
- na listagem (`ListGeneratedPosts`), por linha
- no topo da tela de edição (`EditGeneratedPost`)

## Como testar

Pré-requisitos:
- banco migrado
- dados de `GeneratedPost` existentes
- integração LLM pode estar fake/mock no ambiente de teste

Checklist manual:

1. Abrir Filament em `Generated Posts`.
2. Editar um post existente.
3. Verificar se as seções aparecem na ordem esperada.
4. Editar e salvar os campos:
   - `title`, `slug`, `meta_title`, `meta_description`, `excerpt`, `content`, `status`
5. Confirmar visualização dos scores (`seo_score`, `tone_score`, `readability_score`).
6. Confirmar visualização da última auditoria SEO.
7. Confirmar visualização da última auditoria editorial.
8. Confirmar visualização do histórico em **Histórico de versões** (relation manager).
9. Confirmar visualização de logs em **Logs LLM relacionados**.
10. Executar ações: checklist SEO, auditoria editorial, gerar metadados, aprovar, solicitar ajustes.
11. Salvar edição com `change_summary` e confirmar criação de versão (quando houver mudança de conteúdo/meta).

## Limitações de UX (MVP)

- `FAQ` e `CTA` ainda usam `KeyValue` (não há editor semântico dedicado).
- Auditorias e logs são exibidos em texto/JSON para velocidade de implementação.
- Não existe comparação visual “diff” entre versões nesta etapa.
- Não foi implementado editor rico custom (React), mantendo foco no escopo MVP.
