<?php

namespace Tests\Unit;

use App\Services\TelegramPromobotMessenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramPromobotMessengerTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_partner_matched_message_posts_html_to_telegram_api(): void
    {
        config([
            'telegram_promobot.token' => 'unit-promobot-token',
            'app.name' => 'BetGenious AI',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $link = 'https://betai.example/referral/REF-55501';

        $this->assertTrue(app(TelegramPromobotMessenger::class)->sendPartnerMatchedMessage(123456789, '55501', $link));

        Http::assertSent(function ($request) use ($link): bool {
            $markup = json_decode((string) $request['reply_markup'], true);

            return str_contains($request->url(), '/botunit-promobot-token/sendMessage')
                && $request['chat_id'] === 123456789
                && $request['parse_mode'] === 'HTML'
                && str_contains((string) $request['text'], '<b>Invite verified!</b>')
                && str_contains((string) $request['text'], '<b>#55501</b>')
                && $markup['inline_keyboard'][0][0]['url'] === $link
                && $markup['inline_keyboard'][0][0]['text'] === 'Register and activate access';
        });
    }

    public function test_send_welcome_message_includes_trial_button(): void
    {
        config([
            'telegram_promobot.token' => 'unit-promobot-token',
            'app.name' => 'BetGenious AI',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $link = 'https://betai.example/integration/telegram/promocode/PROMO-TEST123';

        $this->assertTrue(app(TelegramPromobotMessenger::class)->sendWelcomeMessage(123456789, $link));

        Http::assertSent(function ($request) use ($link): bool {
            $markup = json_decode((string) $request['reply_markup'], true);

            return $request['chat_id'] === 123456789
                && $request['parse_mode'] === 'HTML'
                && str_contains((string) $request['text'], '<b>Welcome to BetGenious AI!</b>')
                && $markup['inline_keyboard'][0][0]['url'] === $link
                && $markup['inline_keyboard'][0][0]['text'] === '⚡️ Claim Free 3-Day Trial';
        });
    }
}
