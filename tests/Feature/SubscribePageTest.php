<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscribePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_page_lists_three_plans(): void
    {
        $html = $this->get(route('subscribe'))
            ->assertOk()
            ->assertSee('Free trial', false)
            ->assertSee('3 months', false)
            ->assertSee('1 year', false)
            ->assertSee('1 month', false)
            ->getContent();

        $this->assertStringContainsString('subscribe-plans-grid', $html);
    }

    public function test_disabled_plans_show_coming_soon_button(): void
    {
        $html = $this->get(route('subscribe'))
            ->assertOk()
            ->getContent();

        $this->assertGreaterThanOrEqual(2, substr_count($html, 'Coming soon'));
    }

    public function test_guest_sees_sign_in_for_free_trial(): void
    {
        $this->get(route('subscribe'))
            ->assertOk()
            ->assertSee('Sign in to start', false)
            ->assertDontSee('Start free trial', false);
    }

    public function test_authenticated_user_can_start_free_trial(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('subscribe.store'), ['plan' => SubscriptionPlans::FREE_TRIAL])
            ->assertRedirect(route('subscribe'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue($user->hasActiveSeeTipsAccess());
        $this->assertNotNull($user->see_tips_expires_at);
        $this->assertTrue($user->see_tips_expires_at->isFuture());
    }

    public function test_cannot_start_disabled_plan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('subscribe.store'), ['plan' => SubscriptionPlans::THREE_MONTHS])
            ->assertRedirect(route('subscribe'))
            ->assertSessionHasErrors('plan');

        $user->refresh();
        $this->assertFalse($user->hasActiveSeeTipsAccess());
    }

    public function test_post_requires_auth(): void
    {
        $this->post(route('subscribe.store'), ['plan' => SubscriptionPlans::FREE_TRIAL])
            ->assertRedirect(route('login'));
    }
}
