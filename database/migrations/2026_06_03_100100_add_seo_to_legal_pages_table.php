<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_pages', function (Blueprint $table): void {
            $table->string('meta_title')->nullable()->after('content');
            $table->string('meta_description', 320)->nullable()->after('meta_title');
            $table->string('og_title')->nullable()->after('meta_description');
            $table->string('og_description', 320)->nullable()->after('og_title');
        });
    }

    public function down(): void
    {
        Schema::table('legal_pages', function (Blueprint $table): void {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'og_title',
                'og_description',
            ]);
        });
    }
};
