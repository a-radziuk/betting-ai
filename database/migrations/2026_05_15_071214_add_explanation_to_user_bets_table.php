<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_bets', function (Blueprint $table) {
            $table->text('explanation')->nullable()->after('prediction_type');
        });
    }

    public function down(): void
    {
        Schema::table('user_bets', function (Blueprint $table) {
            $table->dropColumn('explanation');
        });
    }
};
