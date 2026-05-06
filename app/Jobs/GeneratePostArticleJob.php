<?php

namespace App\Jobs;

use App\Models\ContentBrief;
use App\Services\Content\ArticleGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $brief = ContentBrief::query()->find($this->briefId);

        if (! $brief || ! is_array(data_get($brief->metadata, 'outline'))) {
            return;
        }

        $articleGeneratorService->generate($brief);
    }
}
