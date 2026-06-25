<?php

namespace Tests\Feature;

use App\Models\Promocode;
use App\Models\User;
use App\Services\ReferralPromocodeService;
use App\Support\PendingPromocodeSession;
use App\Support\PromocodeGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralProgramTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'referrals.redeem_days' => 3,
            'referrals.referrer_bonus_days' => 3,
            'referrals.code_prefix' => 'REF-',
        ]);
    }

    public function test_active_subscriber_sees_referral_code_on_dashboard(): void
    {
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => now()->addDays(10),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('Refer a friend'), false)
            ->assertSee(__('Copy code'), false)
            ->assertSee(__('Copy link'), false);

        $promocode = Promocode::query()
            ->where('owner_user_id', $user->id)
            ->whereNull('used_at')
            ->first();

        $this->assertNotNull($promocode);
        $this->assertSame(3, $promocode->days);
        $this->assertStringStartsWith('REF-', $promocode->code);
    }

    public function test_user_without_active_subscription_does_not_see_referral_share(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('Refer a friend'), false);

        $this->assertSame(0, Promocode::query()->where('owner_user_id', $user->id)->count());
    }

    public function test_redeeming_referral_code_grants_days_to_both_users(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $referrer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => '2026-06-20 12:00:00',
        ]);
        $redeemer = User::factory()->create();

        $promocode = app(ReferralPromocodeService::class)->issueForUser($referrer);
        $this->assertNotNull($promocode);

        $this->actingAs($redeemer)
            ->from(route('subscribe'))
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('subscribe'))
            ->assertSessionHasNoErrors();

        $redeemer->refresh();
        $referrer->refresh();
        $promocode->refresh();

        $this->assertSame('2026-06-13 12:00:00', $redeemer->see_tips_expires_at?->toDateTimeString());
        $this->assertSame('2026-06-23 12:00:00', $referrer->see_tips_expires_at?->toDateTimeString());
        $this->assertNotNull($promocode->used_at);
        $this->assertSame($redeemer->id, $promocode->used_by_user_id);

        Carbon::setTestNow();
    }

    public function test_user_cannot_redeem_referral_code_after_previous_promocode(): void
    {
        $referrer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => now()->addDays(10),
        ]);
        $redeemer = User::factory()->create();
        $firstPromocode = PromocodeGenerator::generateUnique(2);
        $referralPromocode = app(ReferralPromocodeService::class)->issueForUser($referrer);
        $this->assertNotNull($referralPromocode);

        $this->actingAs($redeemer)
            ->post(route('subscribe.promocode'), ['code' => $firstPromocode->code])
            ->assertSessionHasNoErrors();

        $this->actingAs($redeemer)
            ->from(route('subscribe'))
            ->post(route('subscribe.promocode'), ['code' => $referralPromocode->code])
            ->assertRedirect(route('subscribe'))
            ->assertSessionHasErrors('code');

        $this->assertNull($referralPromocode->fresh()->used_at);
    }

    public function test_user_cannot_redeem_own_referral_code(): void
    {
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => now()->addDays(10),
        ]);

        $promocode = app(ReferralPromocodeService::class)->issueForUser($user);
        $this->assertNotNull($promocode);

        $this->actingAs($user)
            ->from(route('subscribe'))
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('subscribe'))
            ->assertSessionHasErrors('code');

        $this->assertNull($promocode->fresh()->used_at);
    }

    public function test_referral_link_stores_code_for_guest_and_redirects_to_register(): void
    {
        $referrer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => now()->addDays(10),
        ]);
        $promocode = app(ReferralPromocodeService::class)->issueForUser($referrer);
        $this->assertNotNull($promocode);

        $this->get(route('referral.promocode', $promocode->code))
            ->assertRedirect(route('register'))
            ->assertSessionHas(PendingPromocodeSession::SESSION_KEY, $promocode->code);

        $this->assertNull($promocode->fresh()->used_at);
    }

    public function test_referral_link_redeems_immediately_for_authenticated_user(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $referrer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => '2026-06-20 12:00:00',
        ]);
        $redeemer = User::factory()->create();
        $promocode = app(ReferralPromocodeService::class)->issueForUser($referrer);
        $this->assertNotNull($promocode);

        $this->actingAs($redeemer)
            ->get(route('referral.promocode', $promocode->code))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $this->assertSame('2026-06-13 12:00:00', $redeemer->fresh()->see_tips_expires_at?->toDateTimeString());
        $this->assertSame('2026-06-23 12:00:00', $referrer->fresh()->see_tips_expires_at?->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_new_referral_code_is_issued_after_previous_code_is_used(): void
    {
        $referrer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => now()->addDays(10),
        ]);
        $redeemer = User::factory()->create();

        $firstCode = app(ReferralPromocodeService::class)->issueForUser($referrer);
        $this->assertNotNull($firstCode);

        $this->actingAs($redeemer)
            ->post(route('subscribe.promocode'), ['code' => $firstCode->code]);

        $secondCode = app(ReferralPromocodeService::class)->issueForUser($referrer->fresh());
        $this->assertNotNull($secondCode);
        $this->assertNotSame($firstCode->code, $secondCode->code);
        $this->assertNull($secondCode->used_at);
    }

    public function test_regular_promocode_without_owner_does_not_grant_referrer_bonus(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $referrer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => '2026-06-20 12:00:00',
        ]);
        $redeemer = User::factory()->create();
        $promocode = PromocodeGenerator::generateUnique(4);

        $this->actingAs($redeemer)
            ->post(route('subscribe.promocode'), ['code' => $promocode->code])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-06-20 12:00:00', $referrer->fresh()->see_tips_expires_at?->toDateTimeString());

        Carbon::setTestNow();
    }
}
