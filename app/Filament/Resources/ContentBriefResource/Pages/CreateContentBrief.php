<?php

namespace App\Filament\Resources\ContentBriefResource\Pages;

use App\Filament\Resources\ContentBriefResource;
use App\Models\ContentBrief;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateContentBrief extends CreateRecord
{
    protected static string $resource = ContentBriefResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ContentBrief::STATUS_DRAFT;
        $data['created_by'] = Auth::id();

        return $data;
    }
}
