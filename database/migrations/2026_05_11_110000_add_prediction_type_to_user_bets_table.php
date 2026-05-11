<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_bets', function (Blueprint $table) {
            $table->string('prediction_type', 80)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('user_bets', function (Blueprint $table) {
            $table->dropColumn('prediction_type');
        });
    }
};
