<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('generated_post_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title');
            $table->longText('content');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('change_summary')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['generated_post_id', 'version_number']);
            $table->index(['generated_post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_versions');
    }
};
