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
        Schema::table('user_wallets', function (Blueprint $table) {
            $table->decimal('amount_in_play', 14, 2)->default(0)->after('start_balance');
            $table->decimal('total_result', 14, 2)->default(0)->after('amount_in_play');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_wallets', function (Blueprint $table) {
            $table->dropColumn(['amount_in_play', 'total_result']);
        });
    }
};
