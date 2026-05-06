<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wordpress_publications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('generated_post_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('wordpress_post_id')->nullable();
            $table->text('wordpress_url')->nullable();
            $table->string('status', 32);
            $table->jsonb('request_payload')->nullable();
            $table->jsonb('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamps();

            $table->index(['generated_post_id', 'status']);
            $table->index('wordpress_post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_publications');
    }
};
