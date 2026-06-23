<?php

namespace Tests\Unit;

use App\Services\TelegramPromobotMessenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramPromobotMessengerTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_start_message_posts_to_telegram_api(): void
    {
        config([
            'telegram_promobot.token' => 'unit-promobot-token',
            'app.name' => 'BetAI Pro',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $link = 'https://betai.example/integration/telegram/promocode/PROMO-TEST123';

        $this->assertTrue(app(TelegramPromobotMessenger::class)->sendStartMessage(123456789, 3, $link));

        Http::assertSent(function ($request) use ($link): bool {
            return str_contains($request->url(), '/botunit-promobot-token/sendMessage')
                && $request['chat_id'] === 123456789
                && str_contains((string) $request['text'], '3-day subscription')
                && str_contains((string) $request['text'], 'BetAI Pro')
                && str_contains((string) $request['text'], $link);
        });
    }
}
