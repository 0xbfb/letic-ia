<?php

namespace Tests\Feature;

use App\Models\SourceDocument;
use App\Services\DocumentChunkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_source_document(): void
    {
        $document = SourceDocument::create([
            'title' => 'Guia SEO',
            'file_path' => 'documents/guia-seo.txt',
            'file_type' => 'txt',
        ]);

        $this->assertDatabaseHas('source_documents', [
            'id' => $document->id,
            'status' => SourceDocument::STATUS_UPLOADED,
        ]);
    }

    public function test_it_validates_file_type_length(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        SourceDocument::create([
            'title' => 'Arquivo inválido',
            'file_path' => 'documents/invalido.ext',
            'file_type' => str_repeat('x', 17),
        ]);
    }

    public function test_chunking_generates_chunks(): void
    {
        $document = SourceDocument::create([
            'title' => 'Documento para chunking',
            'file_path' => 'documents/chunking.txt',
            'file_type' => 'txt',
        ]);

        $text = implode("\n\n", array_fill(0, 8, str_repeat('palavra ', 80)));
        $chunks = app(DocumentChunkerService::class)->chunk($text, 500, 100);

        foreach ($chunks as $index => $chunk) {
            DB::table('document_chunks')->insert([
                'source_document_id' => $document->id,
                'chunk_index' => $index,
                'content' => $chunk['content'],
                'token_count' => $chunk['token_count'],
                'metadata' => json_encode($chunk['metadata'], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertGreaterThan(1, count($chunks));
        $this->assertDatabaseCount('document_chunks', count($chunks));
    }
}
