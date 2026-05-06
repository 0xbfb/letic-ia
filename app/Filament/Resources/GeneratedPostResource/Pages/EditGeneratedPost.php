<?php

namespace App\Filament\Resources\GeneratedPostResource\Pages;

use App\Filament\Resources\GeneratedPostResource;
use App\Services\Content\PostVersionService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeneratedPost extends EditRecord
{
    protected static string $resource = GeneratedPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GeneratedPostResource::makeSeoChecklistAction(),
            GeneratedPostResource::makeEditorialAuditAction(),
            GeneratedPostResource::makeGenerateMetadataAction(),
            GeneratedPostResource::makeApproveAction(),
            GeneratedPostResource::makeRequestAdjustmentsAction(),
            GeneratedPostResource::makeBackToReviewAction(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var PostVersionService $postVersionService */
        $postVersionService = app(PostVersionService::class);
        $changeSummary = $this->data['change_summary'] ?? null;

        $postVersionService->createVersionIfChanged($this->record, is_string($changeSummary) ? $changeSummary : null);
    }
}
