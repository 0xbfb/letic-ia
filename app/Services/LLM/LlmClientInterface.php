<?php

namespace App\Services\LLM;

interface LlmClientInterface
{
    /**
     * @return array{embedding: array<int, float>, model: string, provider: string, usage: array<string, int>|null}
     */
    public function generateEmbedding(string $input): array;

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{provider: string, model: string, output_text: string, raw_response: array<string, mixed>|null, usage: array<string, int>|null}
     */
    public function generateText(array $messages, ?string $model = null): array;
}
