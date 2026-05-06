# FEATURE: REVIEW STATUS (GeneratedPost)

## Objetivo

Implementar fluxo editorial simples no `GeneratedPost` apenas com mudança de status e registro de observações de revisão.

## Estados possíveis (escopo desta feature)

- `generated`
- `needs_review`
- `changes_requested`
- `approved`
- `failed`

## Transições permitidas

Ações adicionadas no painel (`GeneratedPostResource`):

- **Aprovar**
  - permitido quando status atual é `generated` ou `needs_review`
  - próximo status: `approved`

- **Solicitar ajustes**
  - permitido quando status atual é `generated`, `needs_review` ou `approved`
  - próximo status: `changes_requested`

- **Voltar para revisão**
  - permitido quando status atual é `changes_requested`, `approved` ou `failed`
  - próximo status: `needs_review`

## Regras da aprovação

Ao aprovar:

- atualiza `status` para `approved`
- preenche `approved_by` com `auth()->id()` quando houver usuário autenticado
- registra `approved_at` em `metadata`

Bloqueios antes da aprovação:

- `content` vazio
- `title` vazio
- `slug` vazio
- `meta_description` vazia
- presença de erros no último checklist SEO (`latestSeoAudit.errors_json` com itens)

Quando houver bloqueio, a ação não altera status e mostra notificação de erro no painel.

## Regras de solicitação de ajustes

Ao solicitar ajustes:

- exige campo obrigatório **Observação para ajustes**
- atualiza `status` para `changes_requested`
- salva observação em `metadata.review_notes[]` com:
  - `type`
  - `note`
  - `created_at`
  - `created_by`
- tenta registrar evento no histórico de versões usando `PostVersionService` com `change_summary`

> Observação: o serviço de versões só cria nova versão se detectar mudança relevante em conteúdo/meta/título.

## Exibição de status no painel

A coluna de status em tabela usa badge com cores por estado:

- `generated`: info
- `needs_review`: gray
- `changes_requested`: warning
- `approved`: success
- `failed`: danger

## Como testar

1. Abrir um `GeneratedPost` com status `generated` ou `needs_review`.
2. Clicar em **Aprovar** com campos obrigatórios preenchidos.
3. Confirmar:
   - status `approved`
   - `approved_by` preenchido
   - `metadata.approved_at` preenchido
4. Em outro post, deixar um campo essencial vazio e tentar aprovar.
5. Confirmar bloqueio da aprovação com notificação de erro.
6. Clicar em **Solicitar ajustes**.
7. Informar observação obrigatória e confirmar.
8. Confirmar:
   - status `changes_requested`
   - observação salva em `metadata.review_notes`
9. Clicar em **Voltar para revisão**.
10. Confirmar status `needs_review`.
11. Verificar badges de status na listagem de `Generated Posts`.
