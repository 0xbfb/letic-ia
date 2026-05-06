<?php

namespace App\Services\Content;

use App\Models\ContentBrief;
use App\Models\SourceDocument;
use App\Services\Documents\DocumentSearchService;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class BriefingBuilderService
{
    public function __construct(private readonly DocumentSearchService $documentSearchService)
    {
    }

    public function buildContext(ContentBrief $brief, int $limit = 8): array
    {
        $query = $this->buildQuery($brief);

        if ($query === '') {
            throw new InvalidArgumentException('O briefing precisa de pelo menos uma palavra-chave, título ou nota para montar contexto.');
        }

        $documentIds = $this->resolveMandatoryDocumentIds($brief);
        $chunks = $this->documentSearchService->search($query, $limit, $documentIds)->map(
            fn ($chunk): array => [
                'chunk_id' => (int) $chunk->id,
                'source_document_id' => (int) $chunk->source_document_id,
                'source_document_title' => (string) $chunk->document_title,
                'chunk_index' => (int) $chunk->chunk_index,
                'content' => (string) $chunk->content,
                'distance' => isset($chunk->distance) ? (float) $chunk->distance : null,
                'similarity' => isset($chunk->similarity) ? (float) $chunk->similarity : null,
            ]
        )->values();

        $context = [
            'query' => $query,
            'brief_id' => $brief->id,
            'mandatory_document_ids' => $documentIds,
            'total_chunks' => $chunks->count(),
            'chunks' => $chunks->all(),
        ];

        $metadata = $brief->getAttribute('metadata') ?? [];
        $metadata['preview_context'] = Arr::except($context, ['chunks']);
        $brief->forceFill(['metadata' => $metadata])->save();

        return $context;
    }

    private function buildQuery(ContentBrief $brief): string
    {
        $segments = [
            $brief->main_keyword,
            implode(' ', $brief->secondary_keywords ?? []),
            $brief->title,
            $brief->notes,
        ];

        return trim(collect($segments)->filter(fn ($segment) => filled($segment))->implode(' | '));
    }

    private function resolveMandatoryDocumentIds(ContentBrief $brief): ?array
    {
        $manualSelectionIds = $brief->sourceDocuments()->pluck('source_documents.id')->all();

        if (! empty($manualSelectionIds)) {
            return $manualSelectionIds;
        }

        $legacyMandatorySources = collect($brief->mandatory_sources ?? [])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->values();

        if ($legacyMandatorySources->isEmpty()) {
            return null;
        }

        return SourceDocument::query()
            ->where(function ($query) use ($legacyMandatorySources): void {
                foreach ($legacyMandatorySources as $sourceReference) {
                    $query->orWhere('title', 'ILIKE', '%'.$sourceReference.'%');
                }
            })
            ->pluck('id')
            ->all();
    }
}
