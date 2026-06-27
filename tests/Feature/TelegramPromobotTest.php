<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTelegramPromobotApiAuth;
use App\Models\Promocode;
use App\Models\SiteText;
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

    public function test_start_command_sends_welcome_without_creating_promocode(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(987654321, '/start'))
            ->assertOk()
            ->assertJson([
                'status' => 'welcome',
            ])
            ->assertJsonMissing(['link']);

        $this->assertNull(Promocode::query()->where('telegram_id', 987654321)->first());

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/botpromobot-test-token/sendMessage')
                && (int) $request['chat_id'] === 987654321
                && str_contains((string) $request['text'], 'Welcome to BetAI Pro')
                && str_contains((string) $request['text'], '5-digit promotion code')
                && ! isset($request['reply_markup']);
        });
    }

    public function test_promo_code_message_creates_promocode_and_returns_registration_link(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(987654321, 'My code is 55502'))
            ->assertOk()
            ->assertJson([
                'status' => 'promo_matched',
            ])
            ->assertJsonStructure(['link']);

        $link = $response->json('link');

        $this->assertIsString($link);
        $this->assertStringStartsWith('https://betai.example/integration/telegram/promocode/', $link);

        $promocode = Promocode::query()->where('telegram_id', 987654321)->first();

        $this->assertNotNull($promocode);
        $this->assertSame(3, $promocode->days);
        $this->assertNull($promocode->used_at);

        Http::assertSent(function ($request) use ($link): bool {
            return str_contains((string) $request['text'], '🚀 Your trial is ready!')
                && str_contains((string) $request['text'], $link)
                && isset($request['reply_markup']);
        });
    }

    public function test_unknown_code_sends_not_found_message_with_standard_link(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(424242, 'hello there'))
            ->assertOk()
            ->assertJson([
                'status' => 'promo_not_found',
            ])
            ->assertJsonStructure(['link']);

        $link = $response->json('link');

        $this->assertNotNull(Promocode::query()->where('telegram_id', 424242)->first());

        Http::assertSent(function ($request) use ($link): bool {
            return str_contains((string) $request['text'], '⚠️ Code not found.')
                && str_contains((string) $request['text'], $link)
                && isset($request['reply_markup']);
        });
    }

    public function test_start_endpoint_uses_configurable_site_text_for_promo_matched_message(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        SiteText::query()->where('key', 'telegram.start.promo_matched')->update([
            'value' => 'Custom promo for :days days at :app: :link',
        ]);
        app(\App\Services\SiteTextRepository::class)->forget();

        $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(424242, '55503'))
            ->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains((string) $request['text'], 'Custom promo for 3 days at BetAI Pro:')
                && str_contains((string) $request['text'], 'https://betai.example/integration/telegram/promocode/');
        });
    }

    public function test_start_endpoint_is_idempotent_for_same_tg_id(): void
    {
        $first = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(555, '55501'))
            ->assertOk()
            ->json('link');

        $second = $this->withTelegramSecret()
            ->postJson('/api/telegram/start', $this->sampleUpdate(555, '55504'))
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
            ->postJson('/api/telegram/start', $this->sampleUpdate(111222333, '55501'))
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
            ->postJson('/api/telegram/start', $this->sampleUpdate(444555666, '55501'))
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
            ->postJson('/api/telegram/start', $this->sampleUpdate(777888999, '55501'))
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
