<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
        });

        if (Schema::hasTable('users')) {
            $now = now();
            foreach (DB::table('users')->pluck('id') as $userId) {
                DB::table('user_wallets')->insertOrIgnore([
                    'user_id' => $userId,
                    'balance' => 0,
                    'currency' => 'EUR',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_wallets');
    }
};
