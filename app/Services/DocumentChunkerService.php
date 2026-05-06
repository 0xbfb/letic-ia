<?php

namespace App\Services;

class DocumentChunkerService
{
    /**
     * @return array<int, array{content:string, token_count:int, metadata:array<string,mixed>}>
     */
    public function chunk(string $text, int $targetChars = 1200, int $overlapChars = 200): array
    {
        $normalized = trim(preg_replace('/\R/u', "\n", $text) ?? '');

        if ($normalized === '') {
            return [];
        }

        $paragraphs = preg_split('/\n{2,}/u', $normalized) ?: [];
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim((string) $paragraph);

            if ($paragraph === '') {
                continue;
            }

            $candidate = $current === '' ? $paragraph : $current."\n\n".$paragraph;

            if (mb_strlen($candidate) <= $targetChars || $current === '') {
                $current = $candidate;
                continue;
            }

            $chunks[] = $this->formatChunk($current);
            $current = $this->overlapPrefix($current, $overlapChars)."\n\n".$paragraph;
            $current = trim($current);
        }

        if ($current !== '') {
            $chunks[] = $this->formatChunk($current);
        }

        if ($chunks === []) {
            $chunks[] = $this->formatChunk($normalized);
        }

        return $chunks;
    }

    /** @return array{content:string, token_count:int, metadata:array<string,mixed>} */
    private function formatChunk(string $content): array
    {
        $content = trim($content);

        return [
            'content' => $content,
            'token_count' => max(1, (int) ceil(mb_strlen($content) / 4)),
            'metadata' => [
                'char_count' => mb_strlen($content),
                'estimated_tokens_method' => 'ceil(chars/4)',
            ],
        ];
    }

    private function overlapPrefix(string $content, int $overlapChars): string
    {
        if ($overlapChars <= 0) {
            return '';
        }

        $length = mb_strlen($content);

        if ($length <= $overlapChars) {
            return $content;
        }

        return mb_substr($content, $length - $overlapChars);
    }
}
