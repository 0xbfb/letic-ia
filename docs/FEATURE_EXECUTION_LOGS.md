# FEATURE: Execution Logs no painel

## Objetivo
Consolidar rastros operacionais do MVP para facilitar investigação de falhas no admin.

## Eventos logados

### LLM runs (`llm_runs`)
Operações principais registradas:
- `generate_embedding`
- `generate_outline`
- `generate_article`
- `generate_metadata`
- `audit_editorial`

Campos relevantes:
- `operation`, `provider`, `model`, `status`
- `duration_ms`
- `prompt_tokens`, `completion_tokens`, `total_tokens`
- `metadata.estimated_cost_usd` (quando existir)
- `error`
- `created_at`

### Jobs
Os jobs principais agora registram contexto operacional com:
- `document_id`, `brief_id`, `post_id` (quando aplicável)
- `operation`
- `duration_ms`
- `error_message`

## Onde consultar no painel

### Resource `LlmRunResource`
Grupo de navegação: **Observabilidade**.

Disponível:
- listagem de runs com status, duração, tokens, custo estimado e erro;
- filtros por `operation`, `status`, `provider` e intervalo de data;
- visualização detalhada com metadata mascarada.

### Resource `WordPressPublicationResource`
Grupo de navegação: **Observabilidade**.

Disponível:
- status da publicação e erro;
- inspeção de `request_payload` e `response_payload`;
- histórico por data.

## Cuidados com dados sensíveis
- payloads exibidos no painel passam por mascaramento de chaves sensíveis (`authorization`, `password`, `token`, `api_key`, `application_password`);
- nunca expor `OPENAI_API_KEY`;
- nunca expor senha de aplicação do WordPress.

## Como testar
1. Rodar jobs de geração de outline/artigo/metadados/auditoria e de envio ao WordPress draft.
2. Abrir **Observabilidade > LLM Runs** e verificar:
   - presença das operações principais;
   - filtros por operação/status/provider/data;
   - erro visível em runs `failed`.
3. Abrir **Observabilidade > WordPress Publications** e verificar:
   - status de envio;
   - `request/response` visíveis;
   - campos sensíveis mascarados.
4. Validar logs da aplicação (arquivo/stack configurado) com `duration_ms` e `error_message`.
