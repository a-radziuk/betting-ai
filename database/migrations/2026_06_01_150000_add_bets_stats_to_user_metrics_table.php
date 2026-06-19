<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_metrics', function (Blueprint $table) {
            $table->json('bets_stats')->nullable()->after('length');
        });
    }

    public function down(): void
    {
        Schema::table('user_metrics', function (Blueprint $table) {
            $table->dropColumn('bets_stats');
        });
    }
};
