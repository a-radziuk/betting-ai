<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_bets', function (Blueprint $table) {
            $table->decimal('wallet_total_result', 14, 2)->default(0)->after('real_return');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_bets', function (Blueprint $table) {
            $table->dropColumn('wallet_total_result');
        });
    }
};
