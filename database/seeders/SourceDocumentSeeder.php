<?php

namespace Database\Seeders;

use App\Models\DocumentChunk;
use App\Models\SourceDocument;
use Illuminate\Database\Seeder;

class SourceDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            [
                'title' => 'Guia de SEO para Clínicas Locais',
                'description' => 'Material de referência sobre SEO local para clínicas e consultórios.',
                'file_path' => 'seeders/guia-seo-clinicas.pdf',
                'file_type' => 'pdf',
                'source_type' => 'upload',
                'status' => SourceDocument::STATUS_EMBEDDED,
                'metadata' => ['language' => 'pt-BR', 'seeded' => true],
            ],
            [
                'title' => 'Tendências de Conteúdo para Blog B2B',
                'description' => 'Resumo interno com práticas editoriais para funis de conteúdo B2B.',
                'file_path' => 'seeders/tendencias-conteudo-b2b.docx',
                'file_type' => 'docx',
                'source_type' => 'upload',
                'status' => SourceDocument::STATUS_CHUNKED,
                'metadata' => ['language' => 'pt-BR', 'seeded' => true],
            ],
        ];

        foreach ($documents as $documentData) {
            $document = SourceDocument::query()->updateOrCreate(
                ['file_path' => $documentData['file_path']],
                $documentData
            );

            $chunks = [
                [
                    'chunk_index' => 0,
                    'content' => 'Introdução ao tema e contexto de busca orgânica para o nicho.',
                    'token_count' => 42,
                    'embedding' => null,
                    'metadata' => ['section' => 'intro'],
                ],
                [
                    'chunk_index' => 1,
                    'content' => 'Práticas de estruturação de conteúdo com headings e intenção de busca.',
                    'token_count' => 51,
                    'embedding' => null,
                    'metadata' => ['section' => 'body'],
                ],
                [
                    'chunk_index' => 2,
                    'content' => 'Checklist de revisão para consistência editorial e SEO on-page.',
                    'token_count' => 47,
                    'embedding' => null,
                    'metadata' => ['section' => 'checklist'],
                ],
            ];

            foreach ($chunks as $chunkData) {
                DocumentChunk::query()->updateOrCreate(
                    [
                        'source_document_id' => $document->id,
                        'chunk_index' => $chunkData['chunk_index'],
                    ],
                    [
                        'content' => $chunkData['content'],
                        'token_count' => $chunkData['token_count'],
                        'embedding' => $chunkData['embedding'],
                        'metadata' => $chunkData['metadata'],
                    ]
                );
            }
        }
    }
}
