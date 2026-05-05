<?php

namespace App\Filament\Resources\SourceDocumentResource\Pages;

use App\Filament\Resources\SourceDocumentResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditSourceDocument extends EditRecord
{
    protected static string $resource = SourceDocumentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $fileType = strtolower(pathinfo($data['file_path'] ?? '', PATHINFO_EXTENSION));

        if (! in_array($fileType, ['txt', 'pdf', 'docx'], true)) {
            throw ValidationException::withMessages([
                'file_path' => 'Somente arquivos TXT, PDF e DOCX são permitidos para extração de texto.',
            ]);
        }

        $data['file_type'] = $fileType;

        return $data;
    }
}
