<?php

namespace Tests\Feature;

use App\Models\Promocode;
use App\Models\User;
use App\Support\PendingPromocodeSession;
use App\Support\PromocodeGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromocodeRedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_redeem_promocode_and_get_see_tips_access(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $user = User::factory()->create();
        $promocode = PromocodeGenerator::generateUnique(4);

        $this->actingAs($user)
            ->from(route('subscribe'))
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('subscribe'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $user->refresh();
        $promocode->refresh();

        $this->assertTrue($user->hasActiveSeeTipsAccess());
        $this->assertTrue($user->hasPrivelege(User::PRIVELEGE_SEE_TIPS));
        $this->assertSame('2026-06-14 12:00:00', $user->see_tips_expires_at?->toDateTimeString());
        $this->assertNotNull($promocode->used_at);
        $this->assertSame($user->id, $promocode->used_by_user_id);

        Carbon::setTestNow();
    }

    public function test_redeeming_promocode_extends_existing_active_access(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => '2026-06-12 12:00:00',
        ]);
        $promocode = PromocodeGenerator::generateUnique(3);

        $this->actingAs($user)
            ->from(route('subscribe'))
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('subscribe'));

        $this->assertSame('2026-06-15 12:00:00', $user->fresh()->see_tips_expires_at?->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_used_promocode_cannot_be_redeemed_again(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $promocode = Promocode::query()->create([
            'code' => PromocodeGenerator::prefix().'TESTCODE',
            'days' => 2,
            'used_at' => now(),
            'used_by_user_id' => $first->id,
        ]);

        $this->actingAs($second)
            ->from(route('subscribe'))
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('subscribe'))
            ->assertSessionHasErrors('code');

        $this->assertFalse($second->fresh()->hasActiveSeeTipsAccess());
    }

    public function test_invalid_promocode_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('subscribe'))
            ->post(route('subscribe.promocode'), [
                'code' => PromocodeGenerator::prefix().'NOTFOUND',
            ])
            ->assertRedirect(route('subscribe'))
            ->assertSessionHasErrors('code');
    }

    public function test_guest_promocode_is_stored_in_session_and_redirects_to_login(): void
    {
        $promocode = PromocodeGenerator::generateUnique(1);

        $this->from('/')
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status')
            ->assertSessionHas(PendingPromocodeSession::SESSION_KEY, $promocode->code);

        $this->assertNull($promocode->fresh()->used_at);
    }

    public function test_login_and_register_show_notice_when_pending_promocode_is_active(): void
    {
        $promocode = PromocodeGenerator::generateUnique(3);

        $this->from('/')
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('login'));

        $message = __('A :days-day tips promocode is ready and will be applied after you :action.', [
            'days' => 3,
            'action' => __('sign in'),
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee($message, false);

        $registerMessage = __('A :days-day tips promocode is ready and will be applied after you :action.', [
            'days' => 3,
            'action' => __('register'),
        ]);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee($registerMessage, false);
    }

    public function test_login_and_register_hide_notice_when_pending_promocode_is_used(): void
    {
        $user = User::factory()->create();
        $promocode = Promocode::query()->create([
            'code' => PromocodeGenerator::prefix().'USEDCODE',
            'days' => 3,
            'used_at' => now(),
            'used_by_user_id' => $user->id,
        ]);

        $this->withSession([
            PendingPromocodeSession::SESSION_KEY => $promocode->code,
        ])
            ->get(route('login'))
            ->assertOk()
            ->assertDontSee('tips promocode is ready', false);

        $this->withSession([
            PendingPromocodeSession::SESSION_KEY => $promocode->code,
        ])
            ->get(route('register'))
            ->assertOk()
            ->assertDontSee('tips promocode is ready', false);
    }

    public function test_guest_promocode_is_redeemed_after_login(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $user = User::factory()->create();
        $promocode = PromocodeGenerator::generateUnique(4);

        $this->from('/')
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('status');

        $user->refresh();
        $promocode->refresh();

        $this->assertTrue($user->hasActiveSeeTipsAccess());
        $this->assertNotNull($promocode->used_at);
        $this->assertSame($user->id, $promocode->used_by_user_id);
        $this->assertNull(session(PendingPromocodeSession::SESSION_KEY));

        Carbon::setTestNow();
    }

    public function test_guest_promocode_is_redeemed_after_registration(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $promocode = PromocodeGenerator::generateUnique(2);

        $this->from('/')
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('login'));

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('status');

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $promocode->refresh();

        $this->assertTrue($user->hasActiveSeeTipsAccess());
        $this->assertNotNull($promocode->used_at);
        $this->assertSame($user->id, $promocode->used_by_user_id);

        Carbon::setTestNow();
    }

    public function test_homepage_shows_promocode_form_for_guests_and_users_without_active_subscription(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="home-promocode"', false)
            ->assertSee(route('subscribe.promocode'), false)
            ->assertSee(__('Apply'), false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('id="home-promocode"', false);
    }

    public function test_homepage_hides_promocode_form_when_user_has_active_subscription(): void
    {
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertDontSee('id="home-promocode"', false)
            ->assertDontSee('name="code"', false);
    }

    public function test_subscribe_page_shows_promocode_form_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe'))
            ->assertOk()
            ->assertSee('name="code"', false)
            ->assertSee(route('subscribe.promocode'), false)
            ->assertSee(__('Apply promocode'), false);
    }
}
