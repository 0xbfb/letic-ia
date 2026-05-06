<?php

namespace App\Filament\Widgets;

use App\Models\ContentBrief;
use App\Models\DocumentChunk;
use App\Models\GeneratedPost;
use App\Models\LlmRun;
use App\Models\SeoAudit;
use App\Models\SourceDocument;
use App\Models\WordPressPublication;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MvpOverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $seoAverage = (float) (SeoAudit::query()->avg('score') ?? 0);

        return [
            Stat::make('Total de documentos', (string) SourceDocument::query()->count()),
            Stat::make('Total de chunks', (string) DocumentChunk::query()->count()),
            Stat::make('Total de briefings', (string) ContentBrief::query()->count()),
            Stat::make('Total de posts', (string) GeneratedPost::query()->count()),
            Stat::make('Score SEO médio', number_format($seoAverage, 1, ',', '.')),
            Stat::make('Chamadas LLM', (string) LlmRun::query()->count()),
            Stat::make(
                'Erros LLM',
                (string) LlmRun::query()->where('status', 'failed')->orWhereNotNull('error')->count(),
            ),
            Stat::make(
                'WP sucesso / falha',
                sprintf(
                    '%d / %d',
                    WordPressPublication::query()->where('status', WordPressPublication::STATUS_DRAFT_CREATED)->count(),
                    WordPressPublication::query()->where('status', WordPressPublication::STATUS_FAILED)->count(),
                ),
            ),
        ];
    }
}
