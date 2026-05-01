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
        Schema::create('user_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('event_id');
            $table->bigInteger('odd_id');
            $table->decimal('stake', 14, 2);
            $table->decimal('odds_at_bet', 10, 4);
            $table->decimal('potential_return', 14, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events');
            $table->index(['user_id', 'created_at']);
            $table->index('event_id');
            $table->index('odd_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bets');
    }
};
