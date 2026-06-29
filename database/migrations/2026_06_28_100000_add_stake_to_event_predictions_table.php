<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_predictions', function (Blueprint $table): void {
            $table->decimal('stake', 10, 2)->nullable()->after('bank_percentage');
            $table->unsignedSmallInteger('bank_percentage')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('event_predictions', function (Blueprint $table): void {
            $table->dropColumn('stake');
            $table->unsignedSmallInteger('bank_percentage')->nullable(false)->change();
        });
    }
};
