<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('results');
            $table->json('additional_data')->nullable();
            $table->date('date');
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('event_id')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
            $table->index(['tournament_id', 'date']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_results');
    }
};
