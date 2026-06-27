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

        $now = now();

        $texts = [
            [
                'key' => 'telegram.start.welcome',
                'group' => 'telegram',
                'label' => 'Telegram welcome (/start)',
                'value' => "👋 Welcome to :app!\n\nEnter your 5-digit promotion code here to unlock your trial access.\n\nDon't have a code? Send any message and we'll share our standard free trial.",
            ],
            [
                'key' => 'telegram.start.promo_matched',
                'group' => 'telegram',
                'label' => 'Telegram promo code matched',
                'value' => "🚀 Your trial is ready!\n\nHere is your instant access to the :app AI football analytics platform.\n\nTap the link below to create your account and enjoy :days days fully free:\n:link",
            ],
            [
                'key' => 'telegram.start.promo_not_found',
                'group' => 'telegram',
                'label' => 'Telegram promo code not found',
                'value' => "⚠️ Code not found.\n\nPlease check the digits and try again.\n\nOr grab your standard free access below:\n:link",
            ],
        ];

        foreach ($texts as $text) {
            if (DB::table('site_texts')->where('key', $text['key'])->exists()) {
                continue;
            }

            DB::table('site_texts')->insert([
                ...$text,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_texts')) {
            return;
        }

        DB::table('site_texts')->whereIn('key', [
            'telegram.start.welcome',
            'telegram.start.promo_matched',
            'telegram.start.promo_not_found',
        ])->delete();
    }
};
