<?php

namespace App\Jobs;

use App\Models\GeneratedPost;
use App\Services\WordPress\WordPressPostPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPostToWordPressJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'wordpress';

    public function __construct(
        public readonly int $generatedPostId,
        public readonly ?int $publishedBy = null,
    ) {
    }

    public function handle(WordPressPostPublisher $publisher): void
    {
        $post = GeneratedPost::query()->findOrFail($this->generatedPostId);

        try {
            $publisher->publishDraft($post, $this->publishedBy);
        } catch (\Throwable $exception) {
            Log::error('Falha ao enviar post para WordPress.', [
                'post_id' => $this->generatedPostId,
                'operation' => 'send_post_to_wordpress',
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
