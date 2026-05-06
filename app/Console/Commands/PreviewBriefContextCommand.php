<?php

namespace App\Console\Commands;

use App\Models\ContentBrief;
use App\Services\Content\BriefingBuilderService;
use Illuminate\Console\Command;
use Throwable;

class PreviewBriefContextCommand extends Command
{
    protected $signature = 'briefs:preview-context {brief_id : ID do briefing} {--limit=8 : Quantidade máxima de chunks}';

    protected $description = 'Monta e exibe o contexto de um briefing com busca semântica';

    public function handle(BriefingBuilderService $briefingBuilderService): int
    {
        $briefId = (int) $this->argument('brief_id');
        $limit = (int) $this->option('limit');
        $brief = ContentBrief::query()->find($briefId);

        if (! $brief) {
            $this->error('Briefing não encontrado.');

            return self::FAILURE;
        }

        try {
            $context = $briefingBuilderService->buildContext($brief, $limit);
        } catch (Throwable $exception) {
            $this->error('Falha ao montar contexto: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Query: '.$context['query']);
        $this->line('Brief ID: '.$context['brief_id']);
        $this->line('Documentos obrigatórios: '.json_encode($context['mandatory_document_ids']));
        $this->line('Total de chunks: '.$context['total_chunks']);
        $this->newLine();

        foreach ($context['chunks'] as $chunk) {
            $this->line(sprintf(
                'doc:%d "%s" chunk:%d dist:%s sim:%s',
                $chunk['source_document_id'],
                $chunk['source_document_title'],
                $chunk['chunk_index'],
                number_format((float) ($chunk['distance'] ?? 0), 6, '.', ''),
                number_format((float) ($chunk['similarity'] ?? 0), 6, '.', ''),
            ));
            $this->line(mb_strimwidth(preg_replace('/\s+/', ' ', $chunk['content']), 0, 220, '...'));
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
