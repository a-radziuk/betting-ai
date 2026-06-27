<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promocodes', function (Blueprint $table): void {
            $table->string('partner_code', 5)->nullable()->after('telegram_id');
        });
    }

    public function down(): void
    {
        Schema::table('promocodes', function (Blueprint $table): void {
            $table->dropColumn('partner_code');
        });
    }
};
