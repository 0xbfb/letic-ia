<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_briefs', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('content_type')->nullable();
            $table->string('main_keyword');
            $table->jsonb('secondary_keywords')->nullable();
            $table->string('target_audience');
            $table->string('search_intent');
            $table->string('business_objective');
            $table->string('tone_of_voice');
            $table->string('cta_goal')->nullable();
            $table->unsignedInteger('minimum_words')->nullable();
            $table->unsignedInteger('maximum_words')->nullable();
            $table->jsonb('mandatory_sources')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('content_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_briefs');
    }
};
