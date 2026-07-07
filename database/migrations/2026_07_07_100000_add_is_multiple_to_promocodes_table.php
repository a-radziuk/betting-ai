<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promocodes', function (Blueprint $table): void {
            $table->boolean('is_multiple')->default(false)->after('days');
        });
    }

    public function down(): void
    {
        Schema::table('promocodes', function (Blueprint $table): void {
            $table->dropColumn('is_multiple');
        });
    }
};
