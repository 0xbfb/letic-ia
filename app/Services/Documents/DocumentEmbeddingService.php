<?php

namespace App\Services\Documents;

use App\Models\DocumentChunk;
use App\Models\LlmRun;
use App\Services\LLM\LlmClientInterface;
use App\Services\LLM\OpenAiClient;
use RuntimeException;
use Throwable;

class DocumentEmbeddingService
{
    private LlmClientInterface $llmClient;

    public function __construct()
    {
        $provider = (string) config('llm.provider', 'openai');

        if ($provider !== 'openai') {
            throw new RuntimeException("Provider LLM não suportado para embeddings: {$provider}");
        }

        $this->llmClient = new OpenAiClient();
    }

    public function generateAndStore(DocumentChunk $chunk): void
    {
        $startedAt = microtime(true);

        try {
            $response = $this->llmClient->generateEmbedding($chunk->content);
            $embedding = $response['embedding'];
            $expectedDimensions = (int) config('llm.openai.embedding_dimensions', 1536);

            if (count($embedding) !== $expectedDimensions) {
                throw new RuntimeException("Dimensão de embedding inválida. Esperado {$expectedDimensions}, recebido ".count($embedding).'.');
            }

            $vector = '['.implode(',', $embedding).']';
            $chunk->forceFill(['embedding' => $vector])->save();

            LlmRun::query()->create([
                'provider' => $response['provider'],
                'model' => $response['model'],
                'operation' => 'generate_embedding',
                'related_type' => DocumentChunk::class,
                'related_id' => $chunk->id,
                'status' => 'success',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? null,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? null,
                'total_tokens' => $response['usage']['total_tokens'] ?? null,
            ]);
        } catch (Throwable $exception) {
            LlmRun::query()->create([
                'provider' => (string) config('llm.provider', 'openai'),
                'model' => (string) config('llm.openai.embedding_model', 'text-embedding-3-small'),
                'operation' => 'generate_embedding',
                'related_type' => DocumentChunk::class,
                'related_id' => $chunk->id,
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            throw $exception;
        }
    }
}
