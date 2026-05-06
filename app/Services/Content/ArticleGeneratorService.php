<?php

namespace App\Services\Content;

use App\Models\ContentBrief;
use App\Models\GeneratedPost;
use App\Models\LlmRun;
use App\Services\LLM\OpenAiClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ArticleGeneratorService
{
    public function __construct(
        private readonly BriefingBuilderService $briefingBuilderService,
        private readonly LlmPromptService $llmPromptService,
        private readonly OpenAiClient $llmClient,
    ) {
    }

    public function generate(ContentBrief $brief): ?GeneratedPost
    {
        $startedAt = microtime(true);
        $context = $this->briefingBuilderService->buildContext($brief);
        $chunks = Arr::get($context, 'chunks', []);
        $outline = data_get($brief->metadata, 'outline');
        $prompt = $this->llmPromptService->buildArticlePrompt($brief, is_array($outline) ? $outline : [], $chunks);

        try {
            $response = $this->llmClient->generateText($prompt);
            $parsed = json_decode($response['output_text'], true);

            if (! $this->isValidArticle($parsed)) {
                $this->storeRun($brief, $response, $prompt, 'failed', 'JSON de artigo inválido.', $startedAt);
                Log::warning('Artigo JSON inválido retornado pelo provider.', ['brief_id' => $brief->id, 'operation' => 'generate_article']);

                return null;
            }

            $post = GeneratedPost::create([
                'content_brief_id' => $brief->id,
                'title' => (string) $parsed['title'],
                'slug' => Str::slug((string) $parsed['title']).'-'.$brief->id,
                'excerpt' => (string) $parsed['excerpt'],
                'content' => (string) $parsed['content'],
                'faq_json' => $parsed['faq'],
                'cta_json' => $parsed['ctas'],
                'status' => GeneratedPost::STATUS_NEEDS_REVIEW,
                'created_by' => $brief->created_by,
            ]);

            $this->storeRun($brief, $response, $prompt, 'success', null, $startedAt);

            return $post;
        } catch (Throwable $exception) {
            $this->storeRun($brief, null, $prompt, 'failed', $exception->getMessage(), $startedAt);
            Log::error('Falha ao gerar artigo.', ['brief_id' => $brief->id, 'operation' => 'generate_article', 'error' => $exception->getMessage()]);

            return null;
        }
    }

    private function isValidArticle(mixed $data): bool
    {
        return is_array($data)
            && isset($data['title'], $data['content'], $data['excerpt'], $data['faq'], $data['ctas'])
            && is_string($data['title'])
            && is_string($data['content'])
            && is_string($data['excerpt'])
            && is_array($data['faq'])
            && is_array($data['ctas']);
    }

    private function storeRun(ContentBrief $brief, ?array $response, array $prompt, string $status, ?string $error, float $startedAt): void
    {
        $usage = Arr::get($response, 'usage', []);

        LlmRun::create([
            'provider' => Arr::get($response, 'provider', (string) config('llm.provider', 'openai')),
            'model' => Arr::get($response, 'model', (string) config('llm.openai.chat_model', 'gpt-4.1-mini')),
            'operation' => 'generate_article',
            'related_type' => ContentBrief::class,
            'related_id' => $brief->id,
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
