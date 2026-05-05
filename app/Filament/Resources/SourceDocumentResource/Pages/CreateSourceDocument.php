<?php

namespace App\Filament\Resources\SourceDocumentResource\Pages;

use App\Filament\Resources\SourceDocumentResource;
use App\Models\SourceDocument;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateSourceDocument extends CreateRecord
{
    protected static string $resource = SourceDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $fileType = strtolower(pathinfo($data['file_path'] ?? '', PATHINFO_EXTENSION));

        if (! in_array($fileType, ['txt', 'pdf', 'docx'], true)) {
            throw ValidationException::withMessages([
                'file_path' => 'Somente arquivos txt, pdf e docx são permitidos.',
            ]);
        }

        $data['file_type'] = $fileType;
        $data['status'] = SourceDocument::STATUS_UPLOADED;
        $data['created_by'] = Auth::id();

        return $data;
    }
}
