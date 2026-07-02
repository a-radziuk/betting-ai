<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('score_aet')->nullable()->after('score');
            $table->string('score_pen')->nullable()->after('score_aet');
        });

        Schema::table('event_results', function (Blueprint $table) {
            $table->string('results_aet')->nullable()->after('results');
            $table->string('results_pen')->nullable()->after('results_aet');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['score_aet', 'score_pen']);
        });

        Schema::table('event_results', function (Blueprint $table) {
            $table->dropColumn(['results_aet', 'results_pen']);
        });
    }
};
