<?php

namespace App\Filament\Widgets;

use App\Models\SourceDocument;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentsByStatusStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $counts = SourceDocument::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = [
            SourceDocument::STATUS_UPLOADED,
            SourceDocument::STATUS_EXTRACTING,
            SourceDocument::STATUS_EXTRACTED,
            SourceDocument::STATUS_CHUNKING,
            SourceDocument::STATUS_CHUNKED,
            SourceDocument::STATUS_EMBEDDING,
            SourceDocument::STATUS_EMBEDDED,
            SourceDocument::STATUS_FAILED,
        ];

        return collect($statuses)
            ->map(fn (string $status): Stat => Stat::make($status, (string) ($counts[$status] ?? 0)))
            ->all();
    }
}
