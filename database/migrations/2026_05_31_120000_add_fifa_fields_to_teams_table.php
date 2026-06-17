<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('fifa_name')->nullable()->after('guardian_name');
            $table->unsignedInteger('fifa_rank')->nullable()->after('fifa_name');
            $table->string('fifa_position')->nullable()->after('fifa_rank');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['fifa_name', 'fifa_rank', 'fifa_position']);
        });
    }
};
