<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTelegramPromobotApiAuth;
use App\Models\Promocode;
use App\Models\SiteText;
use App\Models\TelegramInteraction;
use App\Models\User;
use App\Support\PendingPromocodeSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TelegramPromobotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://betai.example',
            'app.name' => 'BetAI Pro',
            'telegram_promobot.days' => 3,
            'telegram_promobot.api_secret' => 'test-telegram-promobot-secret',
            'telegram_promobot.token' => 'promobot-test-token',
            'telegram_promobot.partner_codes' => ['48201', '55501', '84920'],
            'referrals.code_prefix' => 'REF-',
        ]);

        URL::forceRootUrl('https://betai.example');
        URL::forceScheme('https');
    }

    private function sampleUpdate(int $telegramId, string $text = '/start'): array
    {
        return [
            'update_id' => 876543210,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => $telegramId,
                    'is_bot' => false,
                    'first_name' => 'Алексей',
                    'last_name' => 'Петров',
                    'username' => 'aleks_petrov',
                    'language_code' => 'ru',
                ],
                'chat' => [
                    'id' => $telegramId,
                    'first_name' => 'Алексей',
                    'last_name' => 'Петров',
                    'username' => 'aleks_petrov',
                    'type' => 'private',
                ],
                'date' => 1719170000,
                'text' => $text,
            ],
        ];
    }

    private function withTelegramSecret(?string $secret = 'test-telegram-promobot-secret'): static
    {
        if ($secret === null) {
            return $this;
        }

        return $this->withHeader(EnsureTelegramPromobotApiAuth::HEADER, $secret);
    }

    public function test_start_endpoint_requires_api_secret(): void
    {
        $this->postJson('/api/telegram/start', $this->sampleUpdate(12345))
            ->assertUnauthorized();

        $this->withTelegramSecret('wrong-secret')
            ->postJson('/api/telegram/start', $this->sampleUpdate(12345))
            ->assertUnauthorized();
    }

    public function test_start_command_sends_welcome_with_trial_button(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        Carbon::setTestNow('2026-06-27 10:00:00');

        $response = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(987654321, '/start'))
            ->assertOk()
            ->assertJson([
                'status' => 'welcome',
            ])
            ->assertJsonStructure(['link']);

        $link = $response->json('link');

        $this->assertNotNull(Promocode::query()->where('telegram_id', 987654321)->first());

        $interaction = TelegramInteraction::query()->where('telegram_id', 987654321)->first();

        $this->assertNotNull($interaction);

        $this->assertDatabaseHas(
            'telegram_interactions',
            [
                'telegram_id' => 987654321,
                'is_bot' => false,
                'first_name' => 'Алексей',
                'last_name' => 'Петров',
                'username' => 'aleks_petrov',
                'language_code' => 'ru',
                'text' => '/start',
                'created_at' => '2026-06-27 10:00:00',
            ]
        );

        Carbon::setTestNow();

        Http::assertSent(function ($request) use ($link): bool {
            $markup = json_decode((string) $request['reply_markup'], true);

            return str_contains($request->url(), '/botpromobot-test-token/sendMessage')
                && (int) $request['chat_id'] === 987654321
                && $request['parse_mode'] === 'HTML'
                && str_contains((string) $request['text'], '<b>Welcome to BetAI Pro!</b>')
                && str_contains((string) $request['text'], '5-digit code')
                && $markup['inline_keyboard'][0][0]['url'] === $link
                && $markup['inline_keyboard'][0][0]['text'] === '⚡️ Claim Free 3-Day Trial';
        });
    }

    public function test_partner_code_stores_partner_code_on_promocode_and_returns_trial_link(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(987654321, '55501'))
            ->assertOk()
            ->assertJson([
                'status' => 'partner_matched',
                'partner_code' => '55501',
            ])
            ->assertJsonStructure(['link']);

        $link = $response->json('link');

        $promocode = Promocode::query()->where('telegram_id', 987654321)->first();

        $this->assertNotNull($promocode);
        $this->assertSame('55501', $promocode->partner_code);
        $this->assertStringStartsWith('https://betai.example/integration/telegram/promocode/', $link);
        $this->assertSame($link, route('integration.telegram.promocode', [
            'promocode' => $promocode->code,
        ], absolute: true));

        Http::assertSent(function ($request) use ($link): bool {
            $markup = json_decode((string) $request['reply_markup'], true);

            return str_contains((string) $request['text'], '<b>Invite verified!</b>')
                && str_contains((string) $request['text'], '<b>#55501</b>')
                && $markup['inline_keyboard'][0][0]['url'] === $link
                && $markup['inline_keyboard'][0][0]['text'] === 'Register and activate access';
        });
    }

    public function test_partner_code_updates_existing_unused_promocode_with_partner_code(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(987654321, '/start'))
            ->assertOk();

        $promocode = Promocode::query()->where('telegram_id', 987654321)->firstOrFail();
        $this->assertNull($promocode->partner_code);

        $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(987654321, '84920'))
            ->assertOk()
            ->assertJson([
                'status' => 'partner_matched',
                'partner_code' => '84920',
            ]);

        $promocode->refresh();

        $this->assertSame('84920', $promocode->partner_code);
        $this->assertSame(1, Promocode::query()->where('telegram_id', 987654321)->count());
    }

    public function test_invalid_five_digit_code_sends_not_found_message_with_trial_link(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(424242, '99999'))
            ->assertOk()
            ->assertJson([
                'status' => 'promo_not_found',
            ])
            ->assertJsonStructure(['link']);

        $link = $response->json('link');

        $this->assertStringStartsWith('https://betai.example/integration/telegram/promocode/', $link);
        $this->assertNotNull(Promocode::query()->where('telegram_id', 424242)->first());

        Http::assertSent(function ($request) use ($link): bool {
            $markup = json_decode((string) $request['reply_markup'], true);

            return str_contains((string) $request['text'], '<b>Code not found.</b>')
                && $markup['inline_keyboard'][0][0]['url'] === $link
                && $markup['inline_keyboard'][0][0]['text'] === '⚡️ Claim Free 3-Day Trial';
        });
    }

    public function test_non_digit_input_sends_not_found_message_with_trial_link(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(424242, 'hello there'))
            ->assertOk()
            ->assertJson([
                'status' => 'invalid_input',
            ])
            ->assertJsonStructure(['link']);

        $this->assertNotNull(Promocode::query()->where('telegram_id', 424242)->first());
    }

    public function test_start_endpoint_uses_configurable_site_text_for_partner_matched_message(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        SiteText::query()->updateOrInsert(
            ['key' => 'telegram.start.partner_matched'],
            [
                'group' => 'telegram',
                'label' => 'Telegram partner code matched',
                'value' => 'Custom partner #:code at :app',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        app(\App\Services\SiteTextRepository::class)->forget();

        $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(424242, '84920'))
            ->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains((string) $request['text'], 'Custom partner #84920 at BetAI Pro');
        });
    }

    public function test_start_endpoint_updates_existing_telegram_interaction(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        Carbon::setTestNow('2026-06-27 10:00:00');

        $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(555, '/start'))
            ->assertOk();

        Carbon::setTestNow('2026-06-27 11:00:00');

        $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(555, '84920'))
            ->assertOk();

        $this->assertSame(1, TelegramInteraction::query()->where('telegram_id', 555)->count());

        $this->assertDatabaseHas(
            'telegram_interactions',
            [
                'telegram_id' => 555,
                'text' => '84920',
                'created_at' => '2026-06-27 11:00:00',
            ]
        );

        Carbon::setTestNow();
    }

    public function test_trial_link_is_idempotent_for_same_tg_id(): void
    {
        $first = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(555, '/start'))
            ->assertOk()
            ->json('link');

        $second = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(555, '99999'))
            ->assertOk()
            ->json('link');

        $this->assertSame($first, $second);
        $this->assertSame(1, Promocode::query()->where('telegram_id', 555)->count());
    }

    public function test_start_endpoint_rejects_payload_without_message_from_id(): void
    {
        $this->withTelegramSecret()
            ->postJson('/api/telegram/start', [
                'update_id' => 1,
                'message' => [
                    'message_id' => 1,
                    'text' => '/start',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message.from.id']);
    }

    public function test_landing_link_stores_promocode_and_redirects_guest_to_register(): void
    {
        $promocode = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(111222333, '/start'))
            ->assertOk()
            ->json('link');

        $this->get($promocode)
            ->assertRedirect(route('register'))
            ->assertSessionHas('status')
            ->assertSessionHas(PendingPromocodeSession::SESSION_KEY);

        $storedCode = Promocode::query()->where('telegram_id', 111222333)->value('code');

        $this->assertSame($storedCode, session(PendingPromocodeSession::SESSION_KEY));
    }

    public function test_guest_promocode_from_telegram_link_is_redeemed_after_registration(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $link = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(444555666, '/start'))
            ->assertOk()
            ->json('link');

        $this->get($link)->assertRedirect(route('register'));

        $this->post('/register', [
            'email' => 'telegram-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('status');

        $user = User::query()->where('email', 'telegram-user@example.com')->firstOrFail();
        $promocode = Promocode::query()->where('telegram_id', 444555666)->firstOrFail();

        $this->assertTrue($user->hasActiveSeeTipsAccess());
        $this->assertSame('2026-06-13 12:00:00', $user->see_tips_expires_at?->toDateTimeString());
        $this->assertNotNull($promocode->used_at);
        $this->assertSame($user->id, $promocode->used_by_user_id);
        $this->assertNull(session(PendingPromocodeSession::SESSION_KEY));

        Carbon::setTestNow();
    }

    public function test_used_promocode_link_redirects_to_register_with_error(): void
    {
        $promocode = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(777888999, '/start'))
            ->assertOk();

        $code = Promocode::query()->where('telegram_id', 777888999)->value('code');

        Promocode::query()->where('code', $code)->update([
            'used_at' => now(),
        ]);

        $this->get($promocode->json('link'))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('code');
    }
}
