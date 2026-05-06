# FEATURE_DASHBOARD

## Objetivo
Dashboard básico no Filament para acompanhamento operacional do MVP, com métricas rápidas e tabelas leves para contexto recente.

## Métricas exibidas

### Cards principais
- Total de documentos (`source_documents.count`)
- Total de chunks (`document_chunks.count`)
- Total de briefings (`content_briefs.count`)
- Total de posts gerados (`generated_posts.count`)
- Score SEO médio (`seo_audits.avg(score)`)
- Total de chamadas LLM (`llm_runs.count`)
- Chamadas LLM com erro (`llm_runs.status = failed` ou `llm_runs.error != null`)
- Publicações WordPress sucesso/falha (`wordpress_publications.status`)

### Cards por status
- Documentos por status (uploaded, extracting, extracted, chunking, chunked, embedding, embedded, failed)
- Briefings por status (draft, ready_to_generate, generating, generated_outline, generated_article)
- Posts por status (generated, needs_review, changes_requested, approved, failed)

## Tabelas opcionais
Widget de tabela com 3 contextos simples:
- Últimos posts gerados
- Últimos erros operacionais (LLM)
- Últimas publicações WordPress

> Observação: o widget é o mesmo componente com contexto diferente para manter implementação simples e sem duplicação.

## Origem dos dados
- `App\Models\SourceDocument`
- `App\Models\DocumentChunk`
- `App\Models\ContentBrief`
- `App\Models\GeneratedPost`
- `App\Models\SeoAudit`
- `App\Models\LlmRun`
- `App\Models\WordPressPublication`

Todas as consultas usam agregações simples (`count`, `avg`, `group by status`) e listagens recentes com `latest()`.

## Como testar
1. Abrir o painel Filament e acessar Dashboard.
2. Verificar se os cards carregam mesmo com banco vazio (valores 0).
3. Criar alguns registros de documentos/briefings/posts e conferir contagens.
4. Criar ao menos 1 `llm_run` com erro e validar card/tabela de erros.
5. Criar publicações WordPress com status de sucesso e falha e validar card/tabela.

Comandos sugeridos:
- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan test`

## Limitações
- Sem gráficos avançados (escopo MVP).
- Sem filtros temporais customizados.
- Sem exportação.
- Score SEO médio depende da qualidade/completude dos registros em `seo_audits`.
