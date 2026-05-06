<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_brief_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->jsonb('faq_json')->nullable();
            $table->jsonb('cta_json')->nullable();
            $table->string('status', 32)->default('needs_review');
            $table->unsignedSmallInteger('seo_score')->nullable();
            $table->unsignedSmallInteger('readability_score')->nullable();
            $table->unsignedSmallInteger('tone_score')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['content_brief_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_posts');
    }
};
