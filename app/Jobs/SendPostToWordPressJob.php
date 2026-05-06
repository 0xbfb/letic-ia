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
        $startedAt = microtime(true);
        $post = GeneratedPost::query()->findOrFail($this->generatedPostId);

        try {
            $publisher->publishDraft($post, $this->publishedBy);
            Log::info('Job de envio para WordPress finalizado.', [
                'post_id' => $this->generatedPostId,
                'operation' => 'send_post_to_wordpress',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Falha ao enviar post para WordPress.', [
                'post_id' => $this->generatedPostId,
                'operation' => 'send_post_to_wordpress',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
