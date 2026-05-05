<?php

namespace App\Filament\Resources\SourceDocumentResource\Pages;

use App\Filament\Resources\SourceDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSourceDocuments extends ListRecords
{
    protected static string $resource = SourceDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
