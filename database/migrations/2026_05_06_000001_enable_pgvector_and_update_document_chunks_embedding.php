<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        $dimensions = (int) env('OPENAI_EMBEDDING_DIMENSIONS', 1536);

        DB::statement("ALTER TABLE document_chunks ALTER COLUMN embedding TYPE vector({$dimensions}) USING NULL");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE document_chunks ALTER COLUMN embedding TYPE jsonb USING NULL');
    }
};
