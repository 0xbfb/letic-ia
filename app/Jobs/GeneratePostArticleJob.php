<?php

namespace App\Jobs;

use App\Models\ContentBrief;
use App\Services\Content\ArticleGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneratePostArticleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $briefId)
    {
        $this->onQueue('generation');
    }

    public function handle(ArticleGeneratorService $articleGeneratorService): void
    {
        $startedAt = microtime(true);
        $brief = ContentBrief::query()->find($this->briefId);

        if (! $brief) {
            Log::warning('Briefing não encontrado para geração de artigo.', [
                'brief_id' => $this->briefId,
                'operation' => 'generate_article',
            ]);
            return;
        }

        if (! is_array(data_get($brief->metadata, 'outline'))) {
            Log::warning('Outline ausente para geração de artigo.', [
                'brief_id' => $brief->id,
                'operation' => 'generate_article',
            ]);
            return;
        }

        try {
            $articleGeneratorService->generate($brief);
            Log::info('Job de geração de artigo finalizado.', [
                'brief_id' => $brief->id,
                'post_id' => null,
                'operation' => 'generate_article',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (Throwable $exception) {
            Log::error('Falha no job de geração de artigo.', [
                'brief_id' => $brief->id,
                'operation' => 'generate_article',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
