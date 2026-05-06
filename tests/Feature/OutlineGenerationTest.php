<?php

namespace Tests\Feature;

use App\Models\ContentBrief;
use App\Models\SourceDocument;
use App\Services\Content\OutlineGeneratorService;
use App\Services\LLM\OpenAiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OutlineGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_outline_with_mocked_openai_json(): void
    {
        $brief = ContentBrief::factory()->create(['status' => ContentBrief::STATUS_READY_TO_GENERATE]);
        $doc = SourceDocument::factory()->create();
        $brief->sourceDocuments()->attach($doc->id);

        $client = Mockery::mock(OpenAiClient::class);
        $client->shouldReceive('generateText')->once()->andReturn([
            'provider' => 'openai',
            'model' => 'fake-model',
            'output_text' => json_encode([
                'h1' => 'H1',
                'intro_objective' => 'objetivo',
                'sections' => [['title' => 'Seção 1']],
                'cta_plan' => ['primary' => 'Contato'],
            ], JSON_UNESCAPED_UNICODE),
            'raw_response' => ['ok' => true],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20, 'total_tokens' => 30],
        ]);

        $this->app->instance(OpenAiClient::class, $client);

        app(OutlineGeneratorService::class)->generate($brief);

        $brief->refresh();
        $this->assertSame(ContentBrief::STATUS_GENERATED_OUTLINE, $brief->status);
        $this->assertIsArray($brief->metadata['outline'] ?? null);
        $this->assertDatabaseHas('llm_runs', ['related_id' => $brief->id, 'status' => 'success']);
    }

    public function test_it_handles_invalid_outline_json(): void
    {
        $brief = ContentBrief::factory()->create(['status' => ContentBrief::STATUS_READY_TO_GENERATE]);

        $client = Mockery::mock(OpenAiClient::class);
        $client->shouldReceive('generateText')->once()->andReturn([
            'provider' => 'openai',
            'model' => 'fake-model',
            'output_text' => '{"invalid":true}',
            'raw_response' => ['ok' => true],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20, 'total_tokens' => 30],
        ]);

        $this->app->instance(OpenAiClient::class, $client);

        app(OutlineGeneratorService::class)->generate($brief);

        $brief->refresh();
        $this->assertSame(ContentBrief::STATUS_READY_TO_GENERATE, $brief->status);
        $this->assertDatabaseHas('llm_runs', ['related_id' => $brief->id, 'status' => 'failed']);
    }
}
