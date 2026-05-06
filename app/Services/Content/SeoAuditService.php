<?php

namespace App\Services\Content;

use App\Models\GeneratedPost;
use App\Models\SeoAudit;
use Illuminate\Support\Str;

class SeoAuditService
{
    public function runForPost(GeneratedPost $post): SeoAudit
    {
        $post->loadMissing('contentBrief');

        $title = (string) ($post->title ?? '');
        $content = (string) ($post->content ?? '');
        $plainText = trim(strip_tags(Str::markdown($content)));
        $firstParagraph = $this->extractFirstParagraph($content);

        $mainKeyword = trim((string) ($post->contentBrief?->main_keyword ?? ''));
        $secondaryKeywords = collect($post->contentBrief?->secondary_keywords ?? [])
            ->filter(fn ($keyword): bool => is_string($keyword) && trim($keyword) !== '')
            ->map(fn (string $keyword): string => trim($keyword))
            ->values();

        $checks = [];
        $warnings = [];
        $errors = [];

        $checks['main_keyword_in_title'] = $mainKeyword !== '' && $this->contains($title, $mainKeyword);
        $checks['main_keyword_in_first_paragraph'] = $mainKeyword !== '' && $this->contains($firstParagraph, $mainKeyword);

        $secondaryHits = $secondaryKeywords->filter(fn (string $keyword): bool => $this->contains($plainText, $keyword))->count();
        $checks['secondary_keywords_in_text'] = $secondaryKeywords->isEmpty() || $secondaryHits >= 3;

        $checks['has_h2'] = preg_match('/^##\s+.+/m', $content) === 1 || preg_match('/<h2\b[^>]*>.*<\/h2>/is', $content) === 1;

        $metaDescription = trim((string) ($post->meta_description ?? ''));
        $checks['has_meta_description'] = $metaDescription !== '';

        $metaDescriptionLength = mb_strlen($metaDescription);
        $checks['meta_description_length_ok'] = $metaDescriptionLength >= 120 && $metaDescriptionLength <= 160;
        if ($checks['has_meta_description'] && ! $checks['meta_description_length_ok']) {
            $warnings[] = 'Meta description fora do intervalo recomendado (120-160 caracteres).';
        }

        $checks['has_slug'] = trim((string) ($post->slug ?? '')) !== '';

        $checks['has_cta'] = $this->hasCta($post, $content);

        $wordCount = str_word_count(mb_strtolower($plainText));
        $minimumWords = (int) ($post->contentBrief?->minimum_words ?? 600);
        $checks['minimum_text_length'] = $wordCount >= $minimumWords;

        foreach ($this->findLongParagraphWarnings($content) as $warning) {
            $warnings[] = $warning;
        }

        if (! $checks['has_meta_description']) {
            $errors[] = 'Meta description ausente.';
        }

        if (! $checks['has_slug']) {
            $errors[] = 'Slug ausente.';
        }

        if (! $checks['minimum_text_length']) {
            $errors[] = "Texto abaixo do mínimo esperado ({$minimumWords} palavras).";
        }

        $score = $this->calculateScore($checks);

        $audit = SeoAudit::create([
            'generated_post_id' => $post->id,
            'audit_type' => 'seo_checklist',
            'score' => $score,
            'checks_json' => $checks,
            'warnings_json' => $warnings,
            'errors_json' => $errors,
        ]);

        $post->forceFill(['seo_score' => $score])->save();

        return $audit;
    }

    private function calculateScore(array $checks): int
    {
        $weights = [
            'main_keyword_in_title' => 15,
            'main_keyword_in_first_paragraph' => 10,
            'secondary_keywords_in_text' => 10,
            'has_h2' => 10,
            'has_meta_description' => 10,
            'meta_description_length_ok' => 5,
            'has_slug' => 10,
            'has_cta' => 10,
            'minimum_text_length' => 20,
        ];

        $score = 0;

        foreach ($weights as $check => $weight) {
            if (($checks[$check] ?? false) === true) {
                $score += $weight;
            }
        }

        return max(0, min(100, $score));
    }

    private function contains(string $haystack, string $needle): bool
    {
        return Str::contains(mb_strtolower($haystack), mb_strtolower($needle));
    }

    private function extractFirstParagraph(string $content): string
    {
        $normalized = trim($content);

        if ($normalized === '') {
            return '';
        }

        $paragraphs = preg_split('/\n\s*\n/', $normalized);

        return trim((string) ($paragraphs[0] ?? ''));
    }

    private function hasCta(GeneratedPost $post, string $content): bool
    {
        $ctaJson = $post->cta_json ?? [];

        if (is_array($ctaJson) && ! empty($ctaJson)) {
            return true;
        }

        $ctaPatterns = [
            'saiba mais',
            'fale com',
            'entre em contato',
            'clique aqui',
            'solicite',
            'agende',
            'compre agora',
        ];

        $lowerContent = mb_strtolower($content);

        foreach ($ctaPatterns as $pattern) {
            if (str_contains($lowerContent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function findLongParagraphWarnings(string $content): array
    {
        $warnings = [];
        $paragraphs = preg_split('/\n\s*\n/', trim($content)) ?: [];

        foreach ($paragraphs as $index => $paragraph) {
            $words = str_word_count(mb_strtolower(strip_tags($paragraph)));
            if ($words > 120) {
                $paragraphNumber = $index + 1;
                $warnings[] = "Parágrafo {$paragraphNumber} muito longo ({$words} palavras).";
            }
        }

        return $warnings;
    }
}
