<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promocodes', function (Blueprint $table): void {
            $table->unsignedBigInteger('telegram_id')->nullable()->after('days');
            $table->index(['telegram_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::table('promocodes', function (Blueprint $table): void {
            $table->dropIndex(['telegram_id', 'used_at']);
            $table->dropColumn('telegram_id');
        });
    }
};
