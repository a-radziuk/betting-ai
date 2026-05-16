<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_analyses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('event_id')->unique();
            $table->string('event_name');
            $table->string('likely_outcome', 20);
            $table->unsignedTinyInteger('approximate_goals');
            $table->text('description');
            $table->unsignedTinyInteger('home_motivation');
            $table->unsignedTinyInteger('away_motivation');
            $table->unsignedTinyInteger('home_class');
            $table->unsignedTinyInteger('away_class');
            $table->json('influenced_by')->nullable();
            $table->json('influenced_by_event_ids')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_analyses');
    }
};
