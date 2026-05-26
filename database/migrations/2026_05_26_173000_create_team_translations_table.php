<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 12);
            $table->string('name')->nullable();
            $table->string('display_name')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_translations');
    }
};
