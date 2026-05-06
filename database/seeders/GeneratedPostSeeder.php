<?php

namespace Database\Seeders;

use App\Models\ContentBrief;
use App\Models\GeneratedPost;
use App\Models\SeoAudit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GeneratedPostSeeder extends Seeder
{
    public function run(): void
    {
        $briefs = ContentBrief::query()->get()->keyBy('title');

        $posts = [
            ['title' => 'Como aparecer no Google Maps com SEO local', 'brief' => 'Briefing: SEO Local para Clínica Odontológica', 'status' => GeneratedPost::STATUS_NEEDS_REVIEW, 'score' => 62],
            ['title' => 'Checklist SEO para página de serviço odontológico', 'brief' => 'Briefing: SEO Local para Clínica Odontológica', 'status' => GeneratedPost::STATUS_NEEDS_REVIEW, 'score' => 74],
            ['title' => 'Framework de conteúdo B2B para SaaS em crescimento', 'brief' => 'Briefing: Estratégia de Conteúdo B2B para SaaS', 'status' => GeneratedPost::STATUS_APPROVED, 'score' => 88],
            ['title' => 'Erros comuns em blogs B2B e como corrigir', 'brief' => 'Briefing: Estratégia de Conteúdo B2B para SaaS', 'status' => GeneratedPost::STATUS_CHANGES_REQUESTED, 'score' => 55],
        ];

        foreach ($posts as $index => $postData) {
            $briefId = $briefs->get($postData['brief'])?->id;
            if ($briefId === null) {
                continue;
            }

            $post = GeneratedPost::query()->updateOrCreate(
                ['slug' => Str::slug($postData['title'])],
                [
                    'content_brief_id' => $briefId,
                    'title' => $postData['title'],
                    'meta_title' => $postData['title'].' | Letícia SEO MVP',
                    'meta_description' => 'Conteúdo de demonstração para validação local do pipeline editorial.',
                    'excerpt' => 'Post de teste gerado para fluxo de revisão humana.',
                    'content' => "# {$postData['title']}\n\nConteúdo fake para desenvolvimento local.",
                    'faq_json' => [['question' => 'Este conteúdo é real?', 'answer' => 'Não, é apenas dado de seed.']],
                    'cta_json' => ['label' => 'Solicitar revisão', 'url' => 'https://example.test/revisao'],
                    'metadata' => ['seeded' => true, 'position' => $index + 1],
                    'status' => $postData['status'],
                    'seo_score' => $postData['score'],
                    'readability_score' => max(40, $postData['score'] - 8),
                    'tone_score' => min(95, $postData['score'] + 4),
                ]
            );

            SeoAudit::query()->updateOrCreate(
                [
                    'generated_post_id' => $post->id,
                    'audit_type' => 'seo_checklist',
                ],
                [
                    'score' => $postData['score'],
                    'checks_json' => [
                        ['name' => 'keyword_no_title', 'passed' => $postData['score'] >= 70],
                        ['name' => 'meta_description', 'passed' => true],
                    ],
                    'warnings_json' => $postData['score'] >= 70
                        ? ['Ajustar variação semântica no H2 principal.']
                        : ['Adicionar keyword principal no primeiro parágrafo.'],
                    'errors_json' => $postData['score'] >= 60
                        ? []
                        : ['Densidade de palavra-chave abaixo do mínimo esperado.'],
                ]
            );
        }
    }
}
