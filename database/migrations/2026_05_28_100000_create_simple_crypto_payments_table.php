<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simple_crypto_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_id', 32);
            $table->string('wallet_key', 64);
            $table->string('wallet_label');
            $table->string('wallet_address', 255);
            $table->string('payment_code', 32)->unique();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3);
            $table->string('status', 32)->default('awaiting_payment');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'plan_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simple_crypto_payments');
    }
};
