<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_prediction_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('prediction_type', 80);
            $table->timestamps();

            $table->unique(['user_id', 'prediction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_prediction_subscriptions');
    }
};
