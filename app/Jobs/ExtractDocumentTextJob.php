<?php

namespace App\Jobs;

use App\Models\SourceDocument;
use App\Services\DocumentExtractorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExtractDocumentTextJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $documentId)
    {
    }

    public function handle(DocumentExtractorService $extractor): void
    {
        $document = SourceDocument::query()->find($this->documentId);

        if (! $document) {
            Log::error('Documento não encontrado para extração.', [
                'document_id' => $this->documentId,
                'file_type' => null,
                'status' => 'not_found',
                'error' => 'SourceDocument não encontrado',
            ]);

            return;
        }

        $document->update(['status' => SourceDocument::STATUS_EXTRACTING]);
        $startedAt = microtime(true);

        Log::info('Iniciando extração de texto.', [
            'document_id' => $document->id,
            'file_type' => $document->file_type,
            'status' => $document->status,
        ]);

        try {
            $text = $extractor->extract($document);
            $path = "extracted-documents/{$document->id}.txt";
            Storage::disk('local')->put($path, $text);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $textLength = mb_strlen($text);

            $document->update([
                'extracted_text_path' => $path,
                'status' => SourceDocument::STATUS_EXTRACTED,
                'metadata' => array_merge($document->metadata ?? [], [
                    'last_extraction_error' => null,
                    'last_extraction' => [
                        'duration_ms' => $durationMs,
                        'text_length' => $textLength,
                        'file_type' => $document->file_type,
                        'at' => now()->toIso8601String(),
                    ],
                ]),
            ]);

            Log::info('Extração de texto concluída.', [
                'document_id' => $document->id,
                'file_type' => $document->file_type,
                'status' => SourceDocument::STATUS_EXTRACTED,
                'duration_ms' => $durationMs,
                'text_length' => $textLength,
            ]);
        } catch (Throwable $exception) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $document->update([
                'status' => SourceDocument::STATUS_FAILED,
                'metadata' => array_merge($document->metadata ?? [], [
                    'last_extraction_error' => [
                        'message' => $exception->getMessage(),
                        'at' => now()->toIso8601String(),
                        'file_type' => $document->file_type,
                        'duration_ms' => $durationMs,
                    ],
                ]),
            ]);

            Log::error('Falha ao extrair texto do documento.', [
                'document_id' => $document->id,
                'file_type' => $document->file_type,
                'status' => SourceDocument::STATUS_FAILED,
                'duration_ms' => $durationMs,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
