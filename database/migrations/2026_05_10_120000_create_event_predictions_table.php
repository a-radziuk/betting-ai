<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_predictions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('event_id');
            $table->string('prediction_type', 80);
            $table->unsignedBigInteger('odds_id');
            $table->unsignedSmallInteger('bank_percentage');
            $table->text('explanation');
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->index(['event_id', 'prediction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_predictions');
    }
};
