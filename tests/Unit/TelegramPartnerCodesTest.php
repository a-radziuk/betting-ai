<?php

namespace Tests\Unit;

use App\Support\TelegramPartnerCodes;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TelegramPartnerCodesTest extends TestCase
{
    public function test_detects_start_command(): void
    {
        $this->assertTrue(TelegramPartnerCodes::isStartCommand('/start'));
        $this->assertTrue(TelegramPartnerCodes::isStartCommand('/start@BetGeniousBot'));
        $this->assertFalse(TelegramPartnerCodes::isStartCommand('55501'));
    }

    public function test_matches_only_exact_five_digit_partner_codes(): void
    {
        config(['telegram_promobot.partner_codes' => ['48201', '55501', '84920']]);

        $this->assertSame('55501', TelegramPartnerCodes::matchPartnerCode('55501'));
        $this->assertNull(TelegramPartnerCodes::matchPartnerCode('code 55501 please'));
        $this->assertNull(TelegramPartnerCodes::matchPartnerCode('99999'));
    }

    public function test_builds_partner_referral_link(): void
    {
        config(['referrals.code_prefix' => 'REF-']);
        URL::forceRootUrl('https://betai.example');
        URL::forceScheme('https');

        $this->assertSame(
            'https://betai.example/referral/REF-48201',
            TelegramPartnerCodes::referralLink('48201'),
        );
    }
}
