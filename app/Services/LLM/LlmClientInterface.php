<?php

namespace App\Services\LLM;

interface LlmClientInterface
{
    /**
     * @return array{embedding: array<int, float>, model: string, provider: string, usage: array<string, int>|null}
     */
    public function generateEmbedding(string $input): array;
}
