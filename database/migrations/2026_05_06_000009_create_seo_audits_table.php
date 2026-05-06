<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('generated_post_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->nullable();
            $table->jsonb('checks_json')->nullable();
            $table->jsonb('warnings_json')->nullable();
            $table->jsonb('errors_json')->nullable();
            $table->timestamps();

            $table->index('generated_post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_audits');
    }
};
