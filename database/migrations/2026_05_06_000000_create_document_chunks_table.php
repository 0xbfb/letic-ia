<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_document_id')->constrained('source_documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->unsignedInteger('token_count');
            $table->jsonb('embedding')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['source_document_id', 'chunk_index']);
            $table->index('source_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
