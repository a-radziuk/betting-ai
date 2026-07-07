<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promocode_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promocode_id')->constrained('promocodes')->cascadeOnDelete();
            $table->foreignId('used_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('used_at');
            $table->timestamps();

            $table->unique(['promocode_id', 'used_by_user_id']);
            $table->index('used_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promocode_redemptions');
    }
};
