<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_analyses', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropUnique(['event_id']);
            $table->string('type', 80)->after('event_id');
            $table->index(['event_id', 'type']);
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_analyses', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropIndex(['event_id', 'type']);
            $table->dropColumn('type');
            $table->unique('event_id');
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }
};
