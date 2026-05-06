<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_audits', function (Blueprint $table): void {
            $table->string('audit_type', 32)->default('seo_checklist')->after('generated_post_id');
            $table->index(['generated_post_id', 'audit_type']);
        });

        DB::table('seo_audits')->whereNull('audit_type')->update(['audit_type' => 'seo_checklist']);
    }

    public function down(): void
    {
        Schema::table('seo_audits', function (Blueprint $table): void {
            $table->dropIndex(['generated_post_id', 'audit_type']);
            $table->dropColumn('audit_type');
        });
    }
};
