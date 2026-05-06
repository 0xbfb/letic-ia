<?php

namespace App\Services\Content;

use App\Models\GeneratedPost;
use App\Models\LlmRun;
use App\Services\LLM\OpenAiClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MetadataGeneratorService
{
    public function __construct(
        private readonly LlmPromptService $llmPromptService,
        private readonly OpenAiClient $llmClient,
    ) {
    }

    public function generateForPost(GeneratedPost $post): bool
    {
        $startedAt = microtime(true);
        $brief = $post->contentBrief;
        $prompt = $this->llmPromptService->buildMetadataPrompt(
            title: (string) $post->title,
            content: (string) $post->content,
            mainKeyword: (string) ($brief?->main_keyword ?? ''),
            secondaryKeywords: is_array($brief?->secondary_keywords) ? $brief->secondary_keywords : [],
            targetAudience: (string) ($brief?->target_audience ?? ''),
            searchIntent: (string) ($brief?->search_intent ?? '')
        );

        try {
            $response = $this->llmClient->generateText($prompt);
            $parsed = json_decode($response['output_text'], true);

            if (! $this->isValidMetadataJson($parsed)) {
                $this->storeRun($post, $response, $prompt, 'failed', 'JSON de metadados inválido.', $startedAt);
                $this->applySlugFallback($post);
                Log::warning('Metadados JSON inválido retornado pelo provider.', ['post_id' => $post->id, 'operation' => 'generate_metadata']);

                return false;
            }

            $metadata = $post->metadata ?? [];
            $metadata['suggested_tags'] = $parsed['suggested_tags'];
            $metadata['suggested_category'] = $parsed['suggested_category'];

            $post->forceFill([
                'meta_title' => Str::limit((string) $parsed['seo_title'], 60, ''),
                'meta_description' => Str::of((string) $parsed['meta_description'])->trim()->toString(),
                'slug' => $this->buildSafeSlug((string) $parsed['slug'], (string) $post->title, $post->id),
                'metadata' => $metadata,
            ])->save();

            $this->storeRun($post, $response, $prompt, 'success', null, $startedAt);

            return true;
        } catch (Throwable $exception) {
            $this->storeRun($post, null, $prompt, 'failed', $exception->getMessage(), $startedAt);
            $this->applySlugFallback($post);
            Log::error('Falha ao gerar metadados SEO.', ['post_id' => $post->id, 'operation' => 'generate_metadata', 'error' => $exception->getMessage()]);

            return false;
        }
    }

    private function applySlugFallback(GeneratedPost $post): void
    {
        $post->forceFill([
            'slug' => Str::slug((string) $post->title).'-'.$post->id,
        ])->save();
    }

    private function buildSafeSlug(string $slug, string $title, int $postId): string
    {
        $normalized = Str::slug($slug);

        if ($normalized === '') {
            return Str::slug($title).'-'.$postId;
        }

        return $normalized;
    }

    private function isValidMetadataJson(mixed $data): bool
    {
        return is_array($data)
            && isset($data['seo_title'], $data['meta_description'], $data['slug'], $data['suggested_tags'], $data['suggested_category'])
            && is_string($data['seo_title'])
            && is_string($data['meta_description'])
            && is_string($data['slug'])
            && is_array($data['suggested_tags'])
            && is_string($data['suggested_category']);
    }

    private function storeRun(GeneratedPost $post, ?array $response, array $prompt, string $status, ?string $error, float $startedAt): void
    {
        $usage = Arr::get($response, 'usage', []);

        LlmRun::create([
            'provider' => Arr::get($response, 'provider', (string) config('llm.provider', 'openai')),
            'model' => Arr::get($response, 'model', (string) config('llm.openai.chat_model', 'gpt-4.1-mini')),
            'operation' => 'generate_metadata',
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
