<?php

namespace App\Services\WordPress;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class WordPressClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createDraftPost(array $payload): array
    {
        $baseUrl = rtrim((string) config('wordpress.base_url'), '/');
        $username = (string) config('wordpress.username');
        $applicationPassword = (string) config('wordpress.application_password');

        if (blank($baseUrl) || blank($username) || blank($applicationPassword)) {
            throw new WordPressException('Credenciais do WordPress ausentes. Configure WORDPRESS_BASE_URL, WORDPRESS_USERNAME e WORDPRESS_APPLICATION_PASSWORD.');
        }

        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new WordPressException('WORDPRESS_BASE_URL inválida. Informe uma URL completa, por exemplo: https://seu-site.com');
        }

        $endpoint = $baseUrl.'/wp-json/wp/v2/posts';

        try {
            $response = Http::timeout(30)
                ->withBasicAuth($username, $applicationPassword)
                ->acceptJson()
                ->asJson()
                ->post($endpoint, $payload)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new WordPressException('Erro de conexão ao enviar draft para WordPress.', 0, $exception);
        } catch (RequestException $exception) {
            $status = $exception->response?->status();
            $body = $exception->response?->json() ?? $exception->response?->body();
            throw new WordPressException('Erro HTTP ao enviar draft para WordPress. status='.$status.' body='.json_encode($body, JSON_UNESCAPED_UNICODE), 0, $exception);
        }

        $json = $response->json();

        if (! is_array($json) || ! array_key_exists('id', $json)) {
            throw new WordPressException('Resposta inesperada do WordPress: campo id não encontrado.');
        }

        return $json;
    }
}
