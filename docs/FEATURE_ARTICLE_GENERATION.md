# FEATURE: Article Generation

## Objetivo
Gerar artigo completo a partir de `ContentBrief` com outline existente e contexto semântico (chunks), persistindo em `generated_posts` com revisão humana obrigatória.

## Prompt usado
O prompt é montado em `App\Services\Content\LlmPromptService::buildArticlePrompt` com:
- briefing completo (`title`, keywords, audiência, intenção, objetivo de negócio, tom, CTA, limites de tamanho, notas)
- outline gerado em `metadata.outline`
- chunks relevantes retornados por busca semântica
- regras editoriais básicas no `system prompt`

## Estrutura JSON esperada
```json
{
  "title": "string",
  "content": "markdown string",
  "excerpt": "string",
  "faq": [
    {"question": "string", "answer": "string"}
  ],
  "ctas": [
    {"label": "string", "placement": "string", "goal": "string", "copy": "string"}
  ]
}
```

## Fluxo técnico
1. Action **Gerar artigo** em `ContentBriefResource` (visível apenas quando existe outline).
2. Dispatch de `GeneratePostArticleJob` (fila `generation`).
3. `ArticleGeneratorService`:
   - monta contexto + prompt
   - chama LLM
   - valida JSON
   - cria `GeneratedPost` com status `needs_review`
   - registra `llm_runs` com `operation=generate_article`
4. Em erro (JSON inválido ou exceção), falha é logada e registrada em `llm_runs`.

## Como testar
1. Criar/abrir um briefing com outline em `metadata.outline`.
2. Acionar **Gerar artigo** no painel.
3. Verificar criação de registro em `generated_posts` com:
   - `status=needs_review`
   - `content` em Markdown
   - `faq_json` e `cta_json` preenchidos
4. Verificar registro em `llm_runs` com `operation=generate_article` e `status` correto.
5. Abrir `GeneratedPostResource` para listar, visualizar e editar manualmente o conteúdo.

## Limitações editoriais (etapa atual)
- Não gera metadados SEO avançados nesta etapa.
- Não executa auditoria/checklist SEO.
- Não envia para WordPress.
- Não cria versionamento editorial completo.
- Qualidade final depende de revisão humana antes de aprovação/publicação.
