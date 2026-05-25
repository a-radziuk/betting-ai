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
        if (Schema::hasTable('odds') || ! Schema::hasTable('selections')) {
            return;
        }

        Schema::create('odds', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('selection_id');
            $table->decimal('odds', 10, 4);
            $table->decimal('probability', 5, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();

            $table->foreign('selection_id')->references('id')->on('selections');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odds');
    }
};
