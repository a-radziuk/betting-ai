<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('guardian_standings_url', 2048)->nullable()->after('stoiximan_url');
            $table->string('guardian_results_url', 2048)->nullable()->after('guardian_standings_url');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['guardian_standings_url', 'guardian_results_url']);
        });
    }
};
