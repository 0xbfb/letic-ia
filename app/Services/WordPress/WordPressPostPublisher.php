<?php

namespace App\Services\WordPress;

use App\Models\GeneratedPost;
use App\Models\WordPressPublication;
use Illuminate\Support\Str;

class WordPressPostPublisher
{
    public function __construct(private readonly WordPressClient $wordPressClient)
    {
    }

    public function publishDraft(GeneratedPost $generatedPost, ?int $publishedBy = null): WordPressPublication
    {
        if ($generatedPost->status !== GeneratedPost::STATUS_APPROVED) {
            throw new WordPressException('Somente posts com status approved podem ser enviados ao WordPress.');
        }

        $payload = [
            'title' => $generatedPost->title,
            'content' => $this->normalizeContentToHtml($generatedPost->content),
            'status' => 'draft',
            'slug' => $generatedPost->slug,
            'excerpt' => $generatedPost->excerpt,
        ];

        $publication = WordPressPublication::query()->create([
            'generated_post_id' => $generatedPost->id,
            'status' => WordPressPublication::STATUS_FAILED,
            'request_payload' => $payload,
            'published_by' => $publishedBy,
        ]);

        try {
            $response = $this->wordPressClient->createDraftPost($payload);

            $publication->update([
                'wordpress_post_id' => (int) data_get($response, 'id'),
                'wordpress_url' => data_get($response, 'link'),
                'status' => WordPressPublication::STATUS_DRAFT_CREATED,
                'response_payload' => $response,
                'error_message' => null,
            ]);

            $generatedPost->update(['status' => 'sent_to_wordpress']);
        } catch (WordPressException $exception) {
            $publication->update([
                'status' => WordPressPublication::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $publication->refresh();
    }

    private function normalizeContentToHtml(string $content): string
    {
        return Str::contains($content, ['#', '*', '`', '[', ']', "\n- ", "\n1. "])
            ? Str::markdown($content)
            : $content;
    }
}
