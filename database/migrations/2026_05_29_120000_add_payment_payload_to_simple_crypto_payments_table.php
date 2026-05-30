<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simple_crypto_payments', function (Blueprint $table) {
            $table->json('payment_payload')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('simple_crypto_payments', function (Blueprint $table) {
            $table->dropColumn('payment_payload');
        });
    }
};
