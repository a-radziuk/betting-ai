<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_id', 32);
            $table->string('stripe_payment_intent_id', 255)->unique();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3);
            $table->string('status', 32)->default('pending');
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
