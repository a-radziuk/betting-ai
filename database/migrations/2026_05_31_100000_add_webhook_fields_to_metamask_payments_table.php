<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metamask_payments', function (Blueprint $table) {
            $table->json('payment_payload')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('payment_payload');
        });
    }

    public function down(): void
    {
        Schema::table('metamask_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_payload', 'approved_at']);
        });
    }
};
