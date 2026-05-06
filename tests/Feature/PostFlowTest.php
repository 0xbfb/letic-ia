<?php

namespace Tests\Feature;

use App\Models\ContentBrief;
use App\Models\GeneratedPost;
use App\Services\Content\PostVersionService;
use App\Services\Content\SeoAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_generated_post(): void
    {
        $brief = ContentBrief::factory()->create();

        $post = GeneratedPost::create([
            'content_brief_id' => $brief->id,
            'title' => 'Título SEO',
            'slug' => 'titulo-seo',
            'content' => "## H2\n\n".str_repeat('texto ', 700),
            'meta_description' => str_repeat('a', 130),
            'status' => GeneratedPost::STATUS_GENERATED,
        ]);

        $this->assertDatabaseHas('generated_posts', ['id' => $post->id]);
    }

    public function test_editing_a_post_generates_a_new_version_when_changed(): void
    {
        $post = GeneratedPost::factory()->create();
        $service = app(PostVersionService::class);

        $initial = $service->createInitialVersion($post);

        $post->update(['title' => $post->title.' atualizado']);
        $newVersion = $service->createVersionIfChanged($post, 'Ajuste editorial');

        $this->assertNotNull($initial);
        $this->assertNotNull($newVersion);
        $this->assertSame(2, $newVersion->version_number);
    }

    public function test_seo_checklist_generates_a_seo_audit(): void
    {
        $brief = ContentBrief::factory()->create([
            'main_keyword' => 'energia solar',
            'secondary_keywords' => ['painel solar', 'economia de energia', 'instalação fotovoltaica'],
            'minimum_words' => 100,
        ]);

        $post = GeneratedPost::factory()->create([
            'content_brief_id' => $brief->id,
            'title' => 'Energia solar para casas',
            'meta_description' => str_repeat('b', 130),
            'slug' => 'energia-solar-casas',
            'content' => "energia solar é uma solução eficiente.\n\n## Benefícios\n\n".str_repeat('painel solar economia de energia instalação fotovoltaica ', 30),
        ]);

        $audit = app(SeoAuditService::class)->runForPost($post);

        $this->assertDatabaseHas('seo_audits', [
            'id' => $audit->id,
            'generated_post_id' => $post->id,
            'audit_type' => 'seo_checklist',
        ]);
    }
}
