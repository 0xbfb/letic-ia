# FEATURE: Auditoria Editorial com LLM

## Objetivo
Complementar o checklist SEO automático com avaliação editorial assistida por LLM, sem reescrita automática do artigo.

## Critérios avaliados
A auditoria editorial avalia os checks:
1. `tone_matches_expected`
2. `text_is_clear_for_non_experts`
3. `text_is_not_too_generic`
4. `cta_is_natural`
5. `respects_briefing`
6. `has_no_exaggerated_promises`
7. `has_no_excessive_bureaucratic_language`
8. `has_thematic_sensitivity`

## Entrada enviada ao prompt
- briefing (título, objetivo de negócio, intenção de busca e notas)
- palavra-chave principal
- público-alvo
- tom esperado
- conteúdo do post
- metadados SEO (title/meta_description/slug)

## Formato JSON esperado
```json
{
  "score": 0,
  "checks": {
    "tone_matches_expected": {"ok": true, "score": 0, "reason": "string"},
    "text_is_clear_for_non_experts": {"ok": true, "score": 0, "reason": "string"},
    "text_is_not_too_generic": {"ok": true, "score": 0, "reason": "string"},
    "cta_is_natural": {"ok": true, "score": 0, "reason": "string"},
    "respects_briefing": {"ok": true, "score": 0, "reason": "string"},
    "has_no_exaggerated_promises": {"ok": true, "score": 0, "reason": "string"},
    "has_no_excessive_bureaucratic_language": {"ok": true, "score": 0, "reason": "string"},
    "has_thematic_sensitivity": {"ok": true, "score": 0, "reason": "string"}
  },
  "problems": ["string"],
  "suggestions": ["string"]
}
```

## Persistência
Para manter simplicidade do MVP:
- usa a tabela `seo_audits` com `audit_type = editorial`;
- `score` recebe score editorial;
- `checks_json` recebe checks estruturados;
- `errors_json` recebe `problems`;
- `warnings_json` recebe `suggestions`.

Atualizações em `generated_posts`:
- `tone_score` = score editorial geral;
- `readability_score` = score do check `text_is_clear_for_non_experts` (quando disponível).

Também registra `llm_runs.operation = audit_editorial` com prompt, resposta, uso de tokens e status.

## Painel (Filament)
No `GeneratedPostResource`:
- action de linha: **Rodar auditoria editorial**;
- campos read-only para exibir último resultado editorial (checks, problemas e sugestões);
- colunas de listagem para `tone_score` e `readability_score`.

## Como testar
1. Rodar migrations:
   - `php artisan migrate`
2. Abrir `Generated Posts` no Filament.
3. Executar action **Rodar auditoria editorial**.
4. Validar:
   - novo registro em `seo_audits` com `audit_type=editorial`;
   - `tone_score` e `readability_score` atualizados em `generated_posts`;
   - `llm_runs` com `operation=audit_editorial`;
   - resultados visíveis no formulário.
5. Forçar retorno inválido (mock/fake em teste) e validar:
   - action mostra erro;
   - `llm_runs.status=failed`;
   - sem atualização indevida de scores.

## Limitações da avaliação por LLM
- avaliação editorial é probabilística e pode variar por modelo/configuração;
- não substitui revisão humana;
- score não garante conformidade legal/regulatória;
- checks dependem da qualidade do briefing e do contexto enviado;
- não há reescrita automática nesta etapa por decisão de escopo.
