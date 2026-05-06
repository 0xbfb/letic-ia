<?php

namespace Tests\Feature;

use App\Models\ContentBrief;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BriefFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_content_brief(): void
    {
        $brief = ContentBrief::create([
            'title' => 'Brief SEO Energia Solar',
            'main_keyword' => 'energia solar residencial',
            'target_audience' => 'proprietários de casa',
            'search_intent' => 'informacional',
            'business_objective' => 'gerar leads',
            'tone_of_voice' => 'consultivo',
        ]);

        $this->assertDatabaseHas('content_briefs', [
            'id' => $brief->id,
            'status' => ContentBrief::STATUS_DRAFT,
        ]);
    }

    public function test_it_validates_required_fields_for_content_brief(): void
    {
        $this->expectException(QueryException::class);

        ContentBrief::create([
            'title' => 'Brief incompleto',
        ]);
    }
}
