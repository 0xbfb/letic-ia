<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('source_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_type', 16);
            $table->string('source_type', 64)->default('upload');
            $table->string('status', 32)->default('uploaded');
            $table->string('extracted_text_path')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('file_type');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_documents');
    }
};
