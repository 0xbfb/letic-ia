<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('llm_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 64);
            $table->string('model', 191);
            $table->string('operation', 64);
            $table->string('related_type', 191)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('status', 32);
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['operation', 'status']);
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_runs');
    }
};
