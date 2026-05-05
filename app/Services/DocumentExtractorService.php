<?php

namespace App\Services;

use App\Models\SourceDocument;
use RuntimeException;

class DocumentExtractorService
{
    public function extract(SourceDocument $document): string
    {
        return match ($document->file_type) {
            'txt' => $this->extractTxt($document),
            default => throw new RuntimeException("Tipo de arquivo não suportado para extração: {$document->file_type}"),
        };
    }

    public function extractTxt(SourceDocument $document): string
    {
        $absolutePath = storage_path('app/'.$document->file_path);

        if (! is_file($absolutePath)) {
            throw new RuntimeException("Arquivo não encontrado em {$document->file_path}");
        }

        $text = file_get_contents($absolutePath);

        if ($text === false) {
            throw new RuntimeException('Não foi possível ler o conteúdo do arquivo TXT.');
        }

        return trim($text);
    }
}
