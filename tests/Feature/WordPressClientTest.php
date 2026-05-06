<?php

namespace Tests\Feature;

use App\Services\WordPress\WordPressClient;
use App\Services\WordPress\WordPressException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WordPressClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('wordpress.base_url', 'https://example.test');
        config()->set('wordpress.username', 'api-user');
        config()->set('wordpress.application_password', 'app-pass');
    }

    public function test_it_sends_draft_payload_correctly(): void
    {
        Http::fake([
            'https://example.test/wp-json/wp/v2/posts' => Http::response(['id' => 99, 'status' => 'draft'], 201),
        ]);

        $payload = ['title' => 'Meu post', 'content' => '<p>Conteúdo</p>', 'status' => 'draft'];
        $response = app(WordPressClient::class)->createDraftPost($payload);

        Http::assertSent(fn ($request) => $request->url() === 'https://example.test/wp-json/wp/v2/posts'
            && $request['status'] === 'draft'
            && $request['title'] === 'Meu post');

        $this->assertSame(99, $response['id']);
    }

    public function test_it_handles_http_error_from_wordpress(): void
    {
        Http::fake([
            'https://example.test/wp-json/wp/v2/posts' => Http::response(['message' => 'invalid request'], 422),
        ]);

        $this->expectException(WordPressException::class);
        app(WordPressClient::class)->createDraftPost(['title' => 'X', 'status' => 'draft']);
    }
}
