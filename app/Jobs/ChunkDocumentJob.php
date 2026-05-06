<?php

namespace App\Jobs;

use App\Models\SourceDocument;
use App\Services\DocumentChunkerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ChunkDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $documentId)
    {
    }

    public function handle(DocumentChunkerService $chunker): void
    {
        $document = SourceDocument::query()->with('chunks')->find($this->documentId);
        $startedAt = microtime(true);

        if (! $document) {
            Log::error('Documento não encontrado para chunking.', ['document_id' => $this->documentId]);

            return;
        }

        if (empty($document->extracted_text_path) || ! Storage::disk('local')->exists($document->extracted_text_path)) {
            $document->update(['status' => SourceDocument::STATUS_FAILED]);

            Log::error('Documento sem extracted_text_path válido para chunking.', [
                'document_id' => $document->id,
                'extracted_text_path' => $document->extracted_text_path,
            ]);

            return;
        }

        $document->update(['status' => SourceDocument::STATUS_CHUNKING]);

        try {
            $text = Storage::disk('local')->get($document->extracted_text_path);

            $targetChars = (int) config('chunking.target_chars', 1200);
            $overlapChars = (int) config('chunking.overlap_chars', 200);
            $chunks = $chunker->chunk($text, $targetChars, $overlapChars);

            $document->chunks()->delete();

            foreach ($chunks as $index => $chunk) {
                $document->chunks()->create([
                    'chunk_index' => $index,
                    'content' => $chunk['content'],
                    'token_count' => $chunk['token_count'],
                    'embedding' => null,
                    'metadata' => array_merge($chunk['metadata'], [
                        'source' => 'paragraph_grouping',
                        'target_chars' => $targetChars,
                        'overlap_chars' => $overlapChars,
                    ]),
                ]);
            }

            $document->update([
                'status' => SourceDocument::STATUS_CHUNKED,
                'metadata' => array_merge($document->metadata ?? [], [
                    'last_chunking' => [
                        'chunks_count' => count($chunks),
                        'target_chars' => $targetChars,
                        'overlap_chars' => $overlapChars,
                        'at' => now()->toIso8601String(),
                    ],
                    'last_chunking_error' => null,
                ]),
            ]);

            Log::info('Chunking concluído com sucesso.', [
                'document_id' => $document->id,
                'status' => SourceDocument::STATUS_CHUNKED,
                'chunks_count' => count($chunks),
                'target_chars' => $targetChars,
                'overlap_chars' => $overlapChars,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (Throwable $exception) {
            $document->update([
                'status' => SourceDocument::STATUS_FAILED,
                'metadata' => array_merge($document->metadata ?? [], [
                    'last_chunking_error' => [
                        'message' => $exception->getMessage(),
                        'at' => now()->toIso8601String(),
                    ],
                ]),
            ]);

            Log::error('Falha ao gerar chunks do documento.', [
                'document_id' => $document->id,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
