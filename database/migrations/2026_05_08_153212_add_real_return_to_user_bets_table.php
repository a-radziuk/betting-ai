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
            $table->decimal('real_return', 14, 2)->default(0)->after('potential_return');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_bets', function (Blueprint $table) {
            $table->dropColumn('real_return');
        });
    }
};
