<?php

namespace Tests\Feature;

use App\Models\Promocode;
use App\Models\User;
use App\Support\PendingPromocodeSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'telegram_promobot.days' => 3,
            'telegram_promobot.api_secret' => 'test-telegram-promobot-secret',
        ]);

        URL::forceRootUrl('https://betai.example');
        URL::forceScheme('https');
    }

    public function test_start_endpoint_requires_api_secret(): void
    {
        $this->postJson('/api/telegram/start', ['tg_id' => 12345])
            ->assertUnauthorized();

        $this->withToken('wrong-secret')
            ->postJson('/api/telegram/start', ['tg_id' => 12345])
            ->assertUnauthorized();
    }

    public function test_start_endpoint_creates_promocode_and_returns_registration_link(): void
    {
        $response = $this->withToken('test-telegram-promobot-secret')
            ->postJson('/api/telegram/start', ['tg_id' => 987654321])
            ->assertOk()
            ->assertJsonStructure(['link']);

        $link = $response->json('link');

        $this->assertIsString($link);
        $this->assertStringStartsWith('https://betai.example/integration/telegram/promocode/', $link);

        $promocode = Promocode::query()->where('telegram_id', 987654321)->first();

        $this->assertNotNull($promocode);
        $this->assertSame(3, $promocode->days);
        $this->assertNull($promocode->used_at);
        $this->assertSame($link, route('integration.telegram.promocode', [
            'promocode' => $promocode->code,
        ], absolute: true));
    }

    public function test_start_endpoint_is_idempotent_for_same_tg_id(): void
    {
        $first = $this->withToken('test-telegram-promobot-secret')
            ->postJson('/api/telegram/start', ['tg_id' => 555])
            ->assertOk()
            ->json('link');

        $second = $this->withToken('test-telegram-promobot-secret')
            ->postJson('/api/telegram/start', ['tg_id' => 555])
            ->assertOk()
            ->json('link');

        $this->assertSame($first, $second);
        $this->assertSame(1, Promocode::query()->where('telegram_id', 555)->count());
    }

    public function test_landing_link_stores_promocode_and_redirects_guest_to_register(): void
    {
        $promocode = $this->withToken('test-telegram-promobot-secret')
            ->postJson('/api/telegram/start', ['tg_id' => 111222333])
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

        $link = $this->withToken('test-telegram-promobot-secret')
            ->postJson('/api/telegram/start', ['tg_id' => 444555666])
            ->assertOk()
            ->json('link');

        $this->get($link)->assertRedirect(route('register'));

        $this->post('/register', [
            'name' => 'Telegram User',
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
        $promocode = $this->withToken('test-telegram-promobot-secret')
            ->postJson('/api/telegram/start', ['tg_id' => 777888999])
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
