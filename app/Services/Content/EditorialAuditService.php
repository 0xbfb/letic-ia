<?php

namespace App\Services\Content;

use App\Models\GeneratedPost;
use App\Models\LlmRun;
use App\Models\SeoAudit;
use App\Services\LLM\OpenAiClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class EditorialAuditService
{
    public function __construct(
        private readonly LlmPromptService $llmPromptService,
        private readonly OpenAiClient $llmClient,
    ) {
    }

    public function runForPost(GeneratedPost $post): ?SeoAudit
    {
        $startedAt = microtime(true);
        $post->loadMissing('contentBrief');
        $brief = $post->contentBrief;

        $prompt = $this->llmPromptService->buildEditorialAuditPrompt(
            briefing: [
                'title' => (string) ($brief?->title ?? ''),
                'business_objective' => (string) ($brief?->business_objective ?? ''),
                'search_intent' => (string) ($brief?->search_intent ?? ''),
                'notes' => (string) ($brief?->notes ?? ''),
            ],
            mainKeyword: (string) ($brief?->main_keyword ?? ''),
            targetAudience: (string) ($brief?->target_audience ?? ''),
            expectedTone: (string) ($brief?->tone_of_voice ?? ''),
            content: (string) ($post->content ?? ''),
            seoMetadata: [
                'title' => (string) ($post->meta_title ?? $post->title ?? ''),
                'meta_description' => (string) ($post->meta_description ?? ''),
                'slug' => (string) ($post->slug ?? ''),
            ],
        );

        try {
            $response = $this->llmClient->generateText($prompt);
            $parsed = json_decode($response['output_text'], true);

            if (! $this->isValidEditorialAuditJson($parsed)) {
                $this->storeRun($post, $response, $prompt, 'failed', 'JSON de auditoria editorial inválido.', $startedAt);
                Log::warning('Auditoria editorial retornou JSON inválido.', ['post_id' => $post->id, 'operation' => 'audit_editorial']);

                return null;
            }

            $audit = SeoAudit::create([
                'generated_post_id' => $post->id,
                'audit_type' => 'editorial',
                'score' => (int) $parsed['score'],
                'checks_json' => $parsed['checks'],
                'warnings_json' => $parsed['suggestions'],
                'errors_json' => $parsed['problems'],
            ]);

            $post->forceFill([
                'tone_score' => (int) $parsed['score'],
                'readability_score' => $this->extractReadabilityScore($parsed['checks']),
            ])->save();

            $this->storeRun($post, $response, $prompt, 'success', null, $startedAt);

            return $audit;
        } catch (Throwable $exception) {
            $this->storeRun($post, null, $prompt, 'failed', $exception->getMessage(), $startedAt);
            Log::error('Falha ao executar auditoria editorial.', ['post_id' => $post->id, 'operation' => 'audit_editorial', 'error' => $exception->getMessage()]);

            return null;
        }
    }

    private function isValidEditorialAuditJson(mixed $data): bool
    {
        return is_array($data)
            && isset($data['score'], $data['checks'], $data['problems'], $data['suggestions'])
            && is_int($data['score'])
            && $data['score'] >= 0
            && $data['score'] <= 100
            && is_array($data['checks'])
            && is_array($data['problems'])
            && is_array($data['suggestions']);
    }

    private function extractReadabilityScore(array $checks): ?int
    {
        $clarity = Arr::get($checks, 'text_is_clear_for_non_experts');

        if (! is_array($clarity) || ! is_int($clarity['score'] ?? null)) {
            return null;
        }

        return max(0, min(100, (int) $clarity['score']));
    }

    private function storeRun(GeneratedPost $post, ?array $response, array $prompt, string $status, ?string $error, float $startedAt): void
    {
        $usage = Arr::get($response, 'usage', []);

        LlmRun::create([
            'provider' => Arr::get($response, 'provider', (string) config('llm.provider', 'openai')),
            'model' => Arr::get($response, 'model', (string) config('llm.openai.chat_model', 'gpt-4.1-mini')),
            'operation' => 'audit_editorial',
            'related_type' => GeneratedPost::class,
            'related_id' => $post->id,
            'status' => $status,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'prompt_tokens' => Arr::get($usage, 'input_tokens'),
            'completion_tokens' => Arr::get($usage, 'output_tokens'),
            'total_tokens' => Arr::get($usage, 'total_tokens'),
            'metadata' => [
                'prompt' => $prompt,
                'response' => Arr::get($response, 'raw_response'),
                'raw_text' => Arr::get($response, 'output_text'),
            ],
        ]);
    }
}
