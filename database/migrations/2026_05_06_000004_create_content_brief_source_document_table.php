<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_brief_source_document', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_brief_id')->constrained('content_briefs')->cascadeOnDelete();
            $table->foreignId('source_document_id')->constrained('source_documents')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['content_brief_id', 'source_document_id'], 'cb_sd_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_brief_source_document');
    }
};
