<?php

namespace Database\Factories;

use App\Models\ContentBrief;
use App\Models\GeneratedPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GeneratedPostFactory extends Factory
{
    protected $model = GeneratedPost::class;

    public function definition(): array
    {
        return [
            'content_brief_id' => ContentBrief::factory(),
            'title' => 'Post '.$this->faker->words(3, true),
            'slug' => Str::slug($this->faker->unique()->sentence(4)),
            'meta_title' => 'Meta '.$this->faker->words(4, true),
            'meta_description' => str_repeat('meta ', 26),
            'content' => "## H2\n\n".str_repeat('conteudo seo ', 350),
            'status' => GeneratedPost::STATUS_NEEDS_REVIEW,
        ];
    }
}
