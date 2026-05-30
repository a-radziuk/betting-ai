<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metamask_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_id', 32);
            $table->string('tx_hash', 66)->unique();
            $table->string('token', 8);
            $table->unsignedInteger('amount_cents');
            $table->string('recipient_address', 255);
            $table->string('status', 32)->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metamask_payments');
    }
};
