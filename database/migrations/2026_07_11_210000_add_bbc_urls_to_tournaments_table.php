<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->string('bbc_standings_url', 2048)->nullable()->after('guardian_results_url');
            $table->string('bbc_results_url', 2048)->nullable()->after('bbc_standings_url');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table): void {
            $table->dropColumn(['bbc_standings_url', 'bbc_results_url']);
        });
    }
};
