<?php

namespace App\Services;

use App\Models\SourceDocument;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class DocumentExtractorService
{
    public function extract(SourceDocument $document): string
    {
        return match ($document->file_type) {
            'txt' => $this->extractTxt($document),
            'pdf' => $this->extractPdf($document),
            'docx' => $this->extractDocx($document),
            default => throw new RuntimeException("Tipo de arquivo não suportado para extração: {$document->file_type}"),
        };
    }

    public function extractTxt(SourceDocument $document): string
    {
        $absolutePath = $this->resolveDocumentPath($document);
        $text = file_get_contents($absolutePath);

        if ($text === false) {
            throw new RuntimeException('Não foi possível ler o conteúdo do arquivo TXT.');
        }

        return $this->ensureNotEmptyText($this->normalizeWhitespace($text), 'TXT');
    }

    public function extractPdf(SourceDocument $document): string
    {
        $absolutePath = $this->resolveDocumentPath($document);

        try {
            $pdf = (new Parser())->parseFile($absolutePath);
            $text = $pdf->getText();
        } catch (Throwable $exception) {
            throw new RuntimeException('Falha ao interpretar PDF. Verifique se o arquivo não está corrompido.', previous: $exception);
        }

        return $this->ensureNotEmptyText($this->normalizeWhitespace($text), 'PDF');
    }

    public function extractDocx(SourceDocument $document): string
    {
        $absolutePath = $this->resolveDocumentPath($document);

        try {
            $phpWord = IOFactory::load($absolutePath, 'Word2007');
        } catch (Throwable $exception) {
            throw new RuntimeException('Falha ao interpretar DOCX. Verifique se o arquivo é válido.', previous: $exception);
        }

        $paragraphs = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $paragraph = trim((string) $element->getText());

                    if ($paragraph !== '') {
                        $paragraphs[] = $paragraph;
                    }
                }
            }
        }

        $text = implode("\n\n", $paragraphs);

        return $this->ensureNotEmptyText($this->normalizeWhitespace($text), 'DOCX');
    }

    private function resolveDocumentPath(SourceDocument $document): string
    {
        $absolutePath = storage_path('app/'.$document->file_path);

        if (! is_file($absolutePath)) {
            throw new RuntimeException("Arquivo não encontrado em {$document->file_path}");
        }

        return $absolutePath;
    }

    private function normalizeWhitespace(string $text): string
    {
        $normalized = preg_replace("/\r\n?|\u{000B}/u", "\n", $text);
        $normalized = preg_replace('/[\t\f ]+/u', ' ', (string) $normalized);
        $normalized = preg_replace('/\n{3,}/u', "\n\n", (string) $normalized);

        return trim((string) $normalized);
    }

    private function ensureNotEmptyText(string $text, string $type): string
    {
        if ($text === '') {
            throw new RuntimeException("{$type} sem texto útil para extração. PDF escaneado sem OCR pode retornar vazio.");
        }

        return $text;
    }
}
