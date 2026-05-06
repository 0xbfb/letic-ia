<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BriefsAndPostsByStatusStats;
use App\Filament\Widgets\DocumentsByStatusStats;
use App\Filament\Widgets\MvpOverviewStats;
use App\Filament\Widgets\RecentOperationalTables;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * @return array<int, string|object>
     */
    public function getWidgets(): array
    {
        return [
            MvpOverviewStats::class,
            DocumentsByStatusStats::class,
            BriefsAndPostsByStatusStats::class,
            RecentOperationalTables::make(['context' => 'posts']),
            RecentOperationalTables::make(['context' => 'errors']),
            RecentOperationalTables::make(['context' => 'wordpress']),
        ];
    }
}
