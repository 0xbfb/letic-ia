<?php

namespace Database\Seeders;

use App\Models\ContentBrief;
use App\Models\SourceDocument;
use Illuminate\Database\Seeder;

class ContentBriefSeeder extends Seeder
{
    public function run(): void
    {
        $briefs = [
            [
                'title' => 'Briefing: SEO Local para Clínica Odontológica',
                'content_type' => 'artigo',
                'main_keyword' => 'seo local para clínica odontológica',
                'secondary_keywords' => ['google meu negócio', 'seo para dentista'],
                'target_audience' => 'Gestores de clínicas odontológicas',
                'search_intent' => 'informacional',
                'business_objective' => 'gerar leads qualificados',
                'tone_of_voice' => 'consultivo',
                'cta_goal' => 'solicitar diagnóstico de SEO',
                'minimum_words' => 1200,
                'maximum_words' => 1800,
                'mandatory_sources' => ['Guia de SEO para Clínicas Locais'],
                'metadata' => ['seeded' => true],
                'status' => ContentBrief::STATUS_READY_TO_GENERATE,
            ],
            [
                'title' => 'Briefing: Estratégia de Conteúdo B2B para SaaS',
                'content_type' => 'artigo',
                'main_keyword' => 'estratégia de conteúdo b2b saas',
                'secondary_keywords' => ['funil de conteúdo b2b', 'blog para saas'],
                'target_audience' => 'Times de marketing B2B',
                'search_intent' => 'comercial investigativa',
                'business_objective' => 'aumentar pipeline de vendas',
                'tone_of_voice' => 'estratégico',
                'cta_goal' => 'agendar consultoria',
                'minimum_words' => 1400,
                'maximum_words' => 2000,
                'mandatory_sources' => ['Tendências de Conteúdo para Blog B2B'],
                'metadata' => ['seeded' => true],
                'status' => ContentBrief::STATUS_READY_TO_GENERATE,
            ],
        ];

        $documents = SourceDocument::query()->get()->keyBy('title');

        foreach ($briefs as $briefData) {
            $brief = ContentBrief::query()->updateOrCreate(
                ['title' => $briefData['title']],
                $briefData
            );

            $sources = collect($briefData['mandatory_sources'])
                ->map(fn (string $title): ?int => $documents->get($title)?->id)
                ->filter()
                ->values()
                ->all();

            if ($sources !== []) {
                $brief->sourceDocuments()->syncWithoutDetaching($sources);
            }
        }
    }
}
