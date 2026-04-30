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
        Schema::create('selections', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('market_id');
            $table->string('name', 50);
            $table->bigInteger('participant_id')->nullable();
            $table->decimal('handicap', 5, 2)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('market_id')->references('id')->on('markets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selections');
    }
};
