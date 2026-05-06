<?php

namespace App\Services\Content;

use App\Models\ContentBrief;

class LlmPromptService
{
    /**
     * @param  array<int, array<string, mixed>>  $chunks
     */
    public function buildOutlinePrompt(ContentBrief $brief, array $chunks): array
    {
        $systemPrompt = <<<'PROMPT'
Você é um estrategista de conteúdo SEO.
Responda SOMENTE em JSON válido (sem markdown).
Estrutura obrigatória:
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
PROMPT;

        $userPrompt = [
            'brief_title' => $brief->title,
            'main_keyword' => $brief->main_keyword,
            'secondary_keywords' => $brief->secondary_keywords ?? [],
            'target_audience' => $brief->target_audience,
            'search_intent' => $brief->search_intent,
            'business_objective' => $brief->business_objective,
            'tone_of_voice' => $brief->tone_of_voice,
            'desired_cta' => $brief->cta_goal,
            'relevant_chunks' => $chunks,
        ];

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => json_encode($userPrompt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
        ];
    }

    /**
     * @param  array<string, mixed>  $outline
     * @param  array<int, array<string, mixed>>  $chunks
     */
    public function buildArticlePrompt(ContentBrief $brief, array $outline, array $chunks): array
    {
        $systemPrompt = <<<'PROMPT'
Você é um redator SEO sênior.
Responda SOMENTE em JSON válido (sem markdown fora do campo content).
Regras editoriais:
- Produza conteúdo original, factual e claro para humanos.
- Use Markdown no campo content com headings consistentes ao outline.
- Respeite tom de voz, objetivo de negócio e intenção de busca.
- Inclua CTA primário e CTAs de apoio ao longo do artigo.
Estrutura obrigatória:
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
PROMPT;

        $userPrompt = [
            'brief' => [
                'title' => $brief->title,
                'content_type' => $brief->content_type,
                'main_keyword' => $brief->main_keyword,
                'secondary_keywords' => $brief->secondary_keywords ?? [],
                'target_audience' => $brief->target_audience,
                'search_intent' => $brief->search_intent,
                'business_objective' => $brief->business_objective,
                'tone_of_voice' => $brief->tone_of_voice,
                'desired_cta' => $brief->cta_goal,
                'minimum_words' => $brief->minimum_words,
                'maximum_words' => $brief->maximum_words,
                'notes' => $brief->notes,
            ],
            'outline' => $outline,
            'relevant_chunks' => $chunks,
        ];

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => json_encode($userPrompt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
        ];
    }
}
