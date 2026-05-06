# FEATURE: Geração de metadados SEO

## Escopo implementado

Foi implementada a geração de metadados SEO para `GeneratedPost`, incluindo:
- `meta_title`
- `meta_description`
- `slug`
- `suggested_tags` e `suggested_category` salvos em `generated_posts.metadata`

> Fora de escopo nesta etapa: checklist/auditoria SEO, fluxo editorial e integração WordPress.

## Regras usadas

A geração orientada por LLM aplica as seguintes regras:
- considerar a palavra-chave principal de forma natural em `seo_title` e `meta_description`;
- limitar `seo_title` para até 60 caracteres;
- pedir `meta_description` entre 120 e 160 caracteres;
- exigir `slug` URL-friendly (minúsculo, sem acento, com hífen);
- validar estrutura JSON antes de salvar.

Regras de robustez no backend:
- `slug` sempre passa por normalização com `Str::slug`;
- se o slug retornado ficar vazio, é usado fallback para `Str::slug(title) + "-" + post_id`;
- se a chamada LLM falhar ou retornar JSON inválido, aplica fallback de slug local.

## Formato JSON esperado

```json
{
  "seo_title": "string",
  "meta_description": "string",
  "slug": "string",
  "suggested_tags": ["string"],
  "suggested_category": "string"
}
```

## Persistência

Campos atualizados em `generated_posts`:
- `meta_title` <= `seo_title`
- `meta_description` <= `meta_description`
- `slug` <= slug normalizado/fallback
- `metadata.suggested_tags`
- `metadata.suggested_category`

Também é registrado `llm_runs` com:
- `operation = generate_metadata`
- `related_type = App\\Models\\GeneratedPost`
- `related_id = id do post`
- status/erro/tempo/tokens/prompt/resposta

## Como testar

1. Garantir migrations aplicadas:
   - `php artisan migrate`
2. Abrir painel Filament e acessar a listagem de posts gerados.
3. Em um registro de `GeneratedPost`, executar ação **Gerar metadados SEO**.
4. Validar no banco:
   - `meta_title` preenchido
   - `meta_description` preenchida
   - `slug` preenchido e URL-friendly
   - `metadata` com `suggested_tags` e `suggested_category`
5. Validar `llm_runs`:
   - existe linha com `operation = generate_metadata`
6. Testar fallback:
   - simular erro de LLM (ex.: `OPENAI_API_KEY` ausente) e executar ação;
   - validar que `slug` foi gerado localmente a partir do título.

## Limitações conhecidas

- Ainda não há auditoria SEO automática nesta etapa.
- Ainda não há testes automatizados cobrindo esse serviço específico.
