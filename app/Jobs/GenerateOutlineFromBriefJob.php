<?php

namespace App\Jobs;

use App\Models\ContentBrief;
use App\Services\Content\OutlineGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $brief = ContentBrief::query()->find($this->briefId);

        if (! $brief) {
            return;
        }

        $outlineGeneratorService->generate($brief);
    }
}
