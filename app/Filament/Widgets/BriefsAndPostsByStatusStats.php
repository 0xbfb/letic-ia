<?php

namespace App\Filament\Widgets;

use App\Models\ContentBrief;
use App\Models\GeneratedPost;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BriefsAndPostsByStatusStats extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $briefCounts = ContentBrief::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $postCounts = GeneratedPost::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $briefStatuses = [
            ContentBrief::STATUS_DRAFT,
            ContentBrief::STATUS_READY_TO_GENERATE,
            ContentBrief::STATUS_GENERATING,
            ContentBrief::STATUS_GENERATED_OUTLINE,
            ContentBrief::STATUS_GENERATED_ARTICLE,
        ];

        $postStatuses = array_keys(GeneratedPost::reviewStatusOptions());

        $briefStats = collect($briefStatuses)
            ->map(fn (string $status): Stat => Stat::make("Briefing: {$status}", (string) ($briefCounts[$status] ?? 0)));

        $postStats = collect($postStatuses)
            ->map(fn (string $status): Stat => Stat::make("Post: {$status}", (string) ($postCounts[$status] ?? 0)));

        return $briefStats->merge($postStats)->all();
    }
}
