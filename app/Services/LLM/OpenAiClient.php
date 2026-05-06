<?php

namespace App\Services\LLM;

use RuntimeException;

class OpenAiClient implements LlmClientInterface
{
    public function generateEmbedding(string $input): array
    {
        $apiKey = (string) config('llm.openai.api_key');
        $model = (string) config('llm.openai.embedding_model');
        $dimensions = (int) config('llm.openai.embedding_dimensions', 1536);

        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY ausente. Configure a chave antes de gerar embeddings.');
        }

        $payload = [
            'model' => $model,
            'input' => $input,
            'dimensions' => $dimensions,
        ];

        $ch = curl_init('https://api.openai.com/v1/embeddings');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $rawResponse = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new RuntimeException('Falha de rede ao chamar OpenAI embeddings: '.$curlError);
        }

        $decoded = json_decode($rawResponse, true);

        if ($statusCode >= 400) {
            $message = $decoded['error']['message'] ?? 'Erro desconhecido no provider OpenAI.';
            throw new RuntimeException('OpenAI embeddings retornou erro HTTP '.$statusCode.': '.$message);
        }

        $embedding = $decoded['data'][0]['embedding'] ?? null;

        if (! is_array($embedding)) {
            throw new RuntimeException('Resposta de embedding inválida: vetor ausente.');
        }

        return [
            'provider' => 'openai',
            'model' => $decoded['model'] ?? $model,
            'embedding' => array_map('floatval', $embedding),
            'usage' => $decoded['usage'] ?? null,
        ];
    }

    public function generateText(array $messages, ?string $model = null): array
    {
        $apiKey = (string) config('llm.openai.api_key');
        $chatModel = $model ?: (string) config('llm.openai.chat_model', 'gpt-4.1-mini');

        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY ausente. Configure a chave antes de gerar outline.');
        }

        $payload = [
            'model' => $chatModel,
            'input' => $messages,
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);

        $rawResponse = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new RuntimeException('Falha de rede ao chamar OpenAI responses: '.$curlError);
        }

        $decoded = json_decode($rawResponse, true);

        if ($statusCode >= 400) {
            $message = $decoded['error']['message'] ?? 'Erro desconhecido no provider OpenAI.';
            throw new RuntimeException('OpenAI responses retornou erro HTTP '.$statusCode.': '.$message);
        }

        $outputText = (string) ($decoded['output_text'] ?? '');

        if ($outputText === '') {
            throw new RuntimeException('Resposta textual vazia ao gerar outline.');
        }

        return [
            'provider' => 'openai',
            'model' => $decoded['model'] ?? $chatModel,
            'output_text' => $outputText,
            'raw_response' => is_array($decoded) ? $decoded : null,
            'usage' => $decoded['usage'] ?? null,
        ];
    }
}
