<?php

namespace App\Jobs;

use App\Models\ContentBrief;
use App\Services\Content\OutlineGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateOutlineFromBriefJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $briefId)
    {
        $this->onQueue('generation');
    }

    public function handle(OutlineGeneratorService $outlineGeneratorService): void
    {
        $startedAt = microtime(true);
        $brief = ContentBrief::query()->find($this->briefId);

        if (! $brief) {
            Log::warning('Briefing não encontrado para geração de outline.', [
                'brief_id' => $this->briefId,
                'operation' => 'generate_outline',
            ]);
            return;
        }

        try {
            $outlineGeneratorService->generate($brief);
            Log::info('Job de geração de outline finalizado.', [
                'brief_id' => $brief->id,
                'operation' => 'generate_outline',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (Throwable $exception) {
            Log::error('Falha no job de geração de outline.', [
                'brief_id' => $brief->id,
                'operation' => 'generate_outline',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
