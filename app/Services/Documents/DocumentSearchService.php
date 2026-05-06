<?php

namespace App\Services\Documents;

use App\Models\DocumentChunk;
use App\Models\LlmRun;
use App\Services\LLM\LlmClientInterface;
use App\Services\LLM\OpenAiClient;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class DocumentSearchService
{
    private LlmClientInterface $llmClient;

    public function __construct()
    {
        $provider = (string) config('llm.provider', 'openai');

        if ($provider !== 'openai') {
            throw new RuntimeException("Provider LLM não suportado para busca semântica: {$provider}");
        }

        $this->llmClient = new OpenAiClient();
    }

    public function search(string $query, int $limit = 8, ?array $documentIds = null): Collection
    {
        $normalizedQuery = trim($query);

        if ($normalizedQuery === '') {
            return collect();
        }

        $startedAt = microtime(true);

        try {
            $response = $this->llmClient->generateEmbedding($normalizedQuery);
            $embedding = $response['embedding'];
            $vectorLiteral = '['.implode(',', $embedding).']';
            $safeLimit = max(1, min($limit, 100));

            $chunks = DocumentChunk::query()
                ->join('source_documents', 'source_documents.id', '=', 'document_chunks.source_document_id')
                ->whereNotNull('document_chunks.embedding')
                ->when(! empty($documentIds), function ($builder) use ($documentIds): void {
                    $builder->whereIn('document_chunks.source_document_id', $documentIds);
                })
                ->select([
                    'document_chunks.id',
                    'document_chunks.source_document_id',
                    'source_documents.title as document_title',
                    'document_chunks.chunk_index',
                    'document_chunks.content',
                ])
                ->selectRaw('document_chunks.embedding <=> ?::vector as distance', [$vectorLiteral])
                ->selectRaw('1 - (document_chunks.embedding <=> ?::vector) as similarity', [$vectorLiteral])
                ->orderBy('distance')
                ->limit($safeLimit)
                ->get();

            LlmRun::query()->create([
                'provider' => $response['provider'],
                'model' => $response['model'],
                'operation' => 'search_query_embedding',
                'status' => 'success',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? null,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? null,
                'total_tokens' => $response['usage']['total_tokens'] ?? null,
                'metadata' => [
                    'query_length' => mb_strlen($normalizedQuery),
                    'limit' => $safeLimit,
                    'document_ids' => $documentIds,
                    'result_count' => $chunks->count(),
                ],
            ]);

            return $chunks;
        } catch (Throwable $exception) {
            LlmRun::query()->create([
                'provider' => (string) config('llm.provider', 'openai'),
                'model' => (string) config('llm.openai.embedding_model', 'text-embedding-3-small'),
                'operation' => 'search_query_embedding',
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'metadata' => [
                    'query_length' => mb_strlen($normalizedQuery),
                    'limit' => $limit,
                    'document_ids' => $documentIds,
                ],
            ]);

            throw $exception;
        }
    }
}
