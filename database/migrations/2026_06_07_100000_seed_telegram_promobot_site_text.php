<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_texts')) {
            return;
        }

        if (DB::table('site_texts')->where('key', 'telegram.start.message')->exists()) {
            return;
        }

        $now = now();

        DB::table('site_texts')->insert([
            'key' => 'telegram.start.message',
            'group' => 'telegram',
            'label' => 'Telegram /start reply message',
            'value' => "Here's your free :days-day subscription to the best AI football analytics website — :app.\n\nRegister: :link",
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_texts')) {
            return;
        }

        DB::table('site_texts')->where('key', 'telegram.start.message')->delete();
    }
};
