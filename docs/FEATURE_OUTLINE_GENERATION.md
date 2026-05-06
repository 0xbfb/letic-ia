# FEATURE: Outline generation from ContentBrief

## Objetivo
Gerar e salvar outline SEO estruturado a partir de um `ContentBrief` com contexto semântico de chunks relevantes.

## Decisão de modelagem
Foi usada a opção **mais simples**: salvar outline em `content_briefs.metadata.outline`.

Motivos:
- já existe coluna `metadata` em `content_briefs`;
- evita nova migration/tabela para este estágio do MVP;
- mantém rastreabilidade com `llm_runs` sem complexidade extra.

## Formato do prompt
`LlmPromptService::buildOutlinePrompt()` envia:
- brief_title
- main_keyword
- secondary_keywords
- target_audience
- search_intent
- business_objective
- tone_of_voice
- desired_cta
- relevant_chunks

Mensagem de sistema exige saída **somente JSON válido** com os campos esperados.

## JSON esperado
```json
{
  "h1": "string",
  "intro_objective": "string",
  "sections": [
    {
      "heading": "string",
      "objective": "string",
      "key_points": ["string"]
    }
  ],
  "cta_plan": {
    "primary_cta": "string",
    "placement": "string",
    "supporting_copy": "string"
  }
}
```

## Fluxo técnico
1. Action `Gerar outline` aparece apenas para briefs `ready_to_generate`.
2. Action atualiza status para `generating` e despacha `GenerateOutlineFromBriefJob` (fila `generation`).
3. Job chama `OutlineGeneratorService`.
4. Service monta contexto (chunks) com `BriefingBuilderService`.
5. Service monta prompt e chama provider via `OpenAiClient::generateText()`.
6. Service valida JSON mínimo (`h1`, `intro_objective`, `sections`, `cta_plan`).
7. Em sucesso:
   - salva em `metadata.outline`;
   - atualiza status para `generated_outline`;
   - registra `llm_runs` com `operation=generate_outline`.

## Tratamento de erro
- Se JSON vier inválido:
  - status do briefing volta para `ready_to_generate`;
  - run é registrado com `status=failed`;
  - resposta bruta (`raw_text`) fica em `llm_runs.metadata`;
  - erro é logado sem quebrar aplicação.
- Se provider falhar (rede, auth, etc):
  - status volta para `ready_to_generate`;
  - erro vai para `llm_runs.error`;
  - aplicação continua operando.

## Registro em llm_runs
Campos gravados:
- provider
- model
- operation (`generate_outline`)
- related_type / related_id
- status
- error
- duration_ms
- prompt_tokens / completion_tokens / total_tokens (se disponíveis)
- metadata.prompt
- metadata.response
- metadata.raw_text

## Painel (Filament)
- Action: `Gerar outline`
- Action: `Ver outline` (modal com h1, objetivo da intro, seções e plano de CTA)
- Status adicionais:
  - `generating`
  - `generated_outline`

## Como testar
1. Criar ou editar briefing completo.
2. Garantir status `ready_to_generate`.
3. Executar action `Gerar outline`.
4. Processar fila (`php artisan queue:work --queue=generation`).
5. Validar:
   - status final `generated_outline`;
   - `metadata.outline` preenchido;
   - tabela `llm_runs` com `operation=generate_outline`.

Teste de erro JSON inválido:
- Mockar/fakear cliente LLM para retornar texto não-JSON.
- Validar status volta para `ready_to_generate` e `llm_runs.status=failed`.
