<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_analyses', function (Blueprint $table) {
            $table->dropUnique(['event_id']);
            $table->string('type', 80)->after('event_id');
            $table->index(['event_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('event_analyses', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'type']);
            $table->dropColumn('type');
            $table->unique('event_id');
        });
    }
};
