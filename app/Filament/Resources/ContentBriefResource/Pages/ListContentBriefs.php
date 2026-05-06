<?php

namespace App\Filament\Resources\ContentBriefResource\Pages;

use App\Filament\Resources\ContentBriefResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContentBriefs extends ListRecords
{
    protected static string $resource = ContentBriefResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
