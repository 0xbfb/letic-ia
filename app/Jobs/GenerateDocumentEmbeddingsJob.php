<?php

namespace App\Jobs;

use App\Models\SourceDocument;
use App\Services\Documents\DocumentEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateDocumentEmbeddingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'embeddings';

    public function __construct(public int $documentId)
    {
    }

    public function handle(DocumentEmbeddingService $embeddingService): void
    {
        $document = SourceDocument::query()->with('chunks')->find($this->documentId);

        if (! $document) {
            Log::error('Documento não encontrado para geração de embeddings.', ['document_id' => $this->documentId]);
            return;
        }

        $document->update(['status' => SourceDocument::STATUS_EMBEDDING]);

        $failedChunks = 0;
        $pendingChunks = $document->chunks->filter(fn ($chunk) => empty($chunk->embedding));

        foreach ($pendingChunks as $chunk) {
            try {
                $embeddingService->generateAndStore($chunk);
            } catch (Throwable $exception) {
                $failedChunks++;
                Log::error('Falha ao gerar embedding para chunk.', [
                    'document_id' => $document->id,
                    'chunk_id' => $chunk->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($failedChunks > 0) {
            $document->update(['status' => SourceDocument::STATUS_FAILED]);
            return;
        }

        $remaining = $document->chunks()->whereNull('embedding')->count();
        if ($remaining === 0) {
            $document->update(['status' => SourceDocument::STATUS_EMBEDDED]);
        }
    }
}
