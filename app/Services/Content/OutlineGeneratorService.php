<?php

namespace App\Services\Content;

use App\Models\ContentBrief;
use App\Models\LlmRun;
use App\Services\LLM\OpenAiClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class OutlineGeneratorService
{
    public function __construct(
        private readonly BriefingBuilderService $briefingBuilderService,
        private readonly LlmPromptService $llmPromptService,
        private readonly OpenAiClient $llmClient,
    ) {
    }

    public function generate(ContentBrief $brief): void
    {
        $startedAt = microtime(true);
        $context = $this->briefingBuilderService->buildContext($brief);
        $chunks = Arr::get($context, 'chunks', []);
        $prompt = $this->llmPromptService->buildOutlinePrompt($brief, $chunks);

        try {
            $response = $this->llmClient->generateText($prompt);
            $parsed = json_decode($response['output_text'], true);

            if (! $this->isValidOutline($parsed)) {
                $this->storeRun($brief, $response, $prompt, 'failed', 'JSON de outline inválido.', $startedAt);
                Log::warning('Outline JSON inválido retornado pelo provider.', ['brief_id' => $brief->id, 'operation' => 'generate_outline']);
                $brief->update(['status' => ContentBrief::STATUS_READY_TO_GENERATE]);

                return;
            }

            $metadata = $brief->metadata ?? [];
            $metadata['outline'] = $parsed;
            $brief->forceFill(['metadata' => $metadata, 'status' => ContentBrief::STATUS_GENERATED_OUTLINE])->save();

            $this->storeRun($brief, $response, $prompt, 'success', null, $startedAt);
        } catch (Throwable $exception) {
            $this->storeRun($brief, null, $prompt, 'failed', $exception->getMessage(), $startedAt);
            Log::error('Falha ao gerar outline.', ['brief_id' => $brief->id, 'operation' => 'generate_outline', 'error' => $exception->getMessage()]);
            $brief->update(['status' => ContentBrief::STATUS_READY_TO_GENERATE]);
        }
    }

    private function isValidOutline(mixed $data): bool
    {
        return is_array($data)
            && isset($data['h1'], $data['intro_objective'], $data['sections'], $data['cta_plan'])
            && is_array($data['sections'])
            && is_array($data['cta_plan']);
    }

    private function storeRun(ContentBrief $brief, ?array $response, array $prompt, string $status, ?string $error, float $startedAt): void
    {
        $usage = Arr::get($response, 'usage', []);

        LlmRun::create([
            'provider' => Arr::get($response, 'provider', (string) config('llm.provider', 'openai')),
            'model' => Arr::get($response, 'model', (string) config('llm.openai.chat_model', 'gpt-4.1-mini')),
            'operation' => 'generate_outline',
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
