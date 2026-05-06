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

    /**
     * @param  array<int, string>  $secondaryKeywords
     */
    public function buildMetadataPrompt(
        string $title,
        string $content,
        string $mainKeyword,
        array $secondaryKeywords,
        string $targetAudience,
        string $searchIntent,
    ): array {
        $systemPrompt = <<<'PROMPT'
Você é especialista em SEO on-page.
Responda SOMENTE em JSON válido (sem markdown).
Objetivo: gerar metadados SEO úteis, claros e naturais.
Regras obrigatórias:
- Considerar a palavra-chave principal em seo_title e meta_description de forma natural.
- seo_title deve ter até 60 caracteres.
- meta_description deve ter entre 120 e 160 caracteres.
- slug deve ser url-friendly (minúsculo, sem acentos, sem espaços, usando hífen).
Estrutura obrigatória:
{
  "seo_title": "string",
  "meta_description": "string",
  "slug": "string",
  "suggested_tags": ["string"],
  "suggested_category": "string"
}
PROMPT;

        $userPrompt = [
            'title' => $title,
            'content' => $content,
            'main_keyword' => $mainKeyword,
            'secondary_keywords' => $secondaryKeywords,
            'target_audience' => $targetAudience,
            'search_intent' => $searchIntent,
        ];

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => json_encode($userPrompt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
        ];
    }

    /**
     * @param  array<string, string>  $briefing
     * @param  array<string, string>  $seoMetadata
     */
    public function buildEditorialAuditPrompt(
        array $briefing,
        string $mainKeyword,
        string $targetAudience,
        string $expectedTone,
        string $content,
        array $seoMetadata,
    ): array {
        $systemPrompt = <<<'PROMPT'
Você é um auditor editorial SEO.
Responda SOMENTE em JSON válido (sem markdown).
Não reescreva o artigo e não gere versão nova do texto.
Avalie tom, clareza e qualidade editorial com foco em público leigo.

Estrutura obrigatória:
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

Regras obrigatórias:
- score geral entre 0 e 100.
- Cada check deve ter score entre 0 e 100.
- Em problems liste riscos editoriais reais e objetivos.
- Em suggestions liste melhorias concretas sem reescrever o artigo completo.
PROMPT;

        $userPrompt = [
            'briefing' => $briefing,
            'main_keyword' => $mainKeyword,
            'target_audience' => $targetAudience,
            'expected_tone' => $expectedTone,
            'post_content' => $content,
            'seo_metadata' => $seoMetadata,
        ];

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => json_encode($userPrompt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
        ];
    }
}
