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
            'telegram.start.welcome' => [
                'group' => 'telegram',
                'label' => 'Telegram welcome (/start)',
                'value' => "👋 <b>Welcome to :app!</b>\n\nIf a friend invited you, please <b>type their 5-digit code</b> below to activate your joint bonus.\n\nDon't have a code? No problem! Just tap the button below to get your standard free trial:",
            ],
            'telegram.start.partner_matched' => [
                'group' => 'telegram',
                'label' => 'Telegram partner code matched',
                'value' => "🤝 <b>Invite verified!</b>\nPartner code <b>#:code</b> accepted. Bonus access has been credited to your friend.\n\nAs a thank-you for joining via a member invite, you've been upgraded to a <b>3-Day VIP Guest Pass</b>. Your portal is ready:",
            ],
            'telegram.start.promo_not_found' => [
                'group' => 'telegram',
                'label' => 'Telegram promo code not found',
                'value' => "⚠️ <b>Code not found.</b>\n\nPlease check the digits and try typing them again. Or simply grab your standard free access below:",
            ],
            'telegram.start.trial_button' => [
                'group' => 'telegram',
                'label' => 'Telegram trial inline button label',
                'value' => '⚡️ Claim Free 3-Day Trial',
            ],
            'telegram.start.partner_button' => [
                'group' => 'telegram',
                'label' => 'Telegram partner inline button label',
                'value' => 'Register and activate access',
            ],
        ];

        foreach ($texts as $key => $text) {
            $updated = DB::table('site_texts')->where('key', $key)->update([
                'group' => $text['group'],
                'label' => $text['label'],
                'value' => $text['value'],
                'updated_at' => $now,
            ]);

            if ($updated === 0) {
                DB::table('site_texts')->insert([
                    'key' => $key,
                    'group' => $text['group'],
                    'label' => $text['label'],
                    'value' => $text['value'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('site_texts')->where('key', 'telegram.start.promo_matched')->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_texts')) {
            return;
        }

        DB::table('site_texts')->whereIn('key', [
            'telegram.start.trial_button',
            'telegram.start.partner_button',
            'telegram.start.partner_matched',
        ])->delete();
    }
};
