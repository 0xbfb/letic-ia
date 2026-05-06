<?php

namespace App\Console\Commands;

use App\Services\Documents\DocumentSearchService;
use Illuminate\Console\Command;
use Throwable;

class DocumentSearchCommand extends Command
{
    protected $signature = 'documents:search {query : Texto da busca semântica} {--limit=8 : Quantidade máxima de chunks} {--document_ids= : IDs de documentos separados por vírgula}';

    protected $description = 'Executa busca semântica nos chunks com pgvector';

    public function handle(DocumentSearchService $searchService): int
    {
        $query = (string) $this->argument('query');
        $limit = (int) $this->option('limit');
        $documentIdsOption = (string) ($this->option('document_ids') ?? '');
        $documentIds = $documentIdsOption === ''
            ? null
            : collect(explode(',', $documentIdsOption))->map(fn (string $id) => (int) trim($id))->filter()->values()->all();

        try {
            $results = $searchService->search($query, $limit, $documentIds);
        } catch (Throwable $exception) {
            $this->error('Falha na busca semântica: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($results->isEmpty()) {
            $this->warn('Nenhum resultado encontrado para a consulta informada.');

            return self::SUCCESS;
        }

        $this->info('Resultados de busca semântica:');
        foreach ($results as $result) {
            $this->line(sprintf(
                '#%d doc:%d "%s" chunk:%d dist:%.6f sim:%.6f',
                $result->id,
                $result->source_document_id,
                $result->document_title,
                $result->chunk_index,
                (float) $result->distance,
                (float) $result->similarity,
            ));
            $this->line(mb_strimwidth(preg_replace('/\s+/', ' ', $result->content), 0, 220, '...'));
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
