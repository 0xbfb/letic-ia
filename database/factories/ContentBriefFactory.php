<?php

namespace Database\Factories;

use App\Models\ContentBrief;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentBriefFactory extends Factory
{
    protected $model = ContentBrief::class;

    public function definition(): array
    {
        return [
            'title' => 'Brief '.$this->faker->words(3, true),
            'content_type' => 'blog_post',
            'main_keyword' => 'palavra chave principal',
            'secondary_keywords' => ['kw1', 'kw2', 'kw3'],
            'target_audience' => 'PMEs',
            'search_intent' => 'informacional',
            'business_objective' => 'gerar leads',
            'tone_of_voice' => 'consultivo',
            'cta_goal' => 'contato',
            'minimum_words' => 600,
            'maximum_words' => 1400,
            'mandatory_sources' => [],
            'status' => ContentBrief::STATUS_DRAFT,
        ];
    }
}
