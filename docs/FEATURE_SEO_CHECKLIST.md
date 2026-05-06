# FEATURE: SEO Checklist Automático (determinístico)

## Objetivo
Implementar auditoria SEO simples sem uso de LLM para posts já gerados, com score objetivo, avisos e erros críticos.

## Regras implementadas
Checklist executado pelo `SeoAuditService`:

1. palavra-chave principal aparece no título;
2. palavra-chave principal aparece no primeiro parágrafo;
3. pelo menos 3 palavras secundárias aparecem no texto (quando existirem);
4. existe pelo menos um H2 (`##` em markdown ou `<h2>` em HTML);
5. existe meta description;
6. meta description entre 120 e 160 caracteres;
7. existe slug;
8. existe CTA (`cta_json` preenchido ou padrões textuais);
9. texto atende tamanho mínimo (`content_briefs.minimum_words`, fallback 600);
10. parágrafos com mais de 120 palavras geram warning.

## Peso do score (0-100)
- `main_keyword_in_title`: 15
- `main_keyword_in_first_paragraph`: 10
- `secondary_keywords_in_text`: 10
- `has_h2`: 10
- `has_meta_description`: 10
- `meta_description_length_ok`: 5
- `has_slug`: 10
- `has_cta`: 10
- `minimum_text_length`: 20

A soma dos checks verdadeiros define o score final limitado entre 0 e 100.

## Persistência
- Cada execução cria um registro em `seo_audits`.
- Campos persistidos:
  - `generated_post_id`
  - `score`
  - `checks_json` (booleanos por regra)
  - `warnings_json` (alertas não críticos)
  - `errors_json` (falhas críticas)
- `generated_posts.seo_score` é atualizado com o score da execução.
- A action pode ser executada múltiplas vezes (gera histórico por post).

## Exemplos de warnings e errors
Warnings:
- `Meta description fora do intervalo recomendado (120-160 caracteres).`
- `Parágrafo 3 muito longo (145 palavras).`

Errors:
- `Meta description ausente.`
- `Slug ausente.`
- `Texto abaixo do mínimo esperado (600 palavras).`

## Painel (Filament)
No `GeneratedPostResource`:
- action de linha: **Rodar checklist SEO**;
- coluna `SEO Score` na listagem;
- exibição do resultado da última auditoria no formulário:
  - checks
  - warnings
  - errors

## Como testar
1. Rodar migrations:
   - `php artisan migrate`
2. Abrir `Generated Posts` no Filament.
3. Executar action **Rodar checklist SEO** em um post.
4. Validar:
   - novo registro em `seo_audits`;
   - `generated_posts.seo_score` atualizado;
   - JSONs de checks/warnings/errors preenchidos;
   - dados visíveis no painel;
   - reexecução gera nova auditoria para o mesmo post.
