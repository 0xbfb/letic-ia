<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_posts', function (Blueprint $table): void {
            $table->jsonb('metadata')->nullable()->after('cta_json');
        });
    }

    public function down(): void
    {
        Schema::table('generated_posts', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};
