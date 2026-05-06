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
}
