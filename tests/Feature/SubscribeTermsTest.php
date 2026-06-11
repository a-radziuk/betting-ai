<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\User;
use App\Support\SubscriptionPlans;
use App\Support\SubscriptionTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscribeTermsTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_button_links_to_terms_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe'))
            ->assertOk()
            ->assertSee(route('subscribe.terms', ['plan' => SubscriptionPlans::ONE_MONTH]), false)
            ->assertDontSee(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]), false);
    }

    public function test_guest_terms_route_redirects_to_login(): void
    {
        $this->get(route('subscribe.terms', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertRedirect(route('login'));
    }

    public function test_payment_redirects_to_terms_without_acceptance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertRedirect(route('subscribe.terms', ['plan' => SubscriptionPlans::ONE_MONTH]));
    }

    public function test_user_can_accept_terms_and_continue_to_payment(): void
    {
        config(['features.subscription_stripe_payments' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe.terms', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertSee('Terms and Conditions', false)
            ->assertSee('Continue to payment', false);

        $this->actingAs($user)
            ->post(route('subscribe.terms.accept', ['plan' => SubscriptionPlans::ONE_MONTH]), [
                'accept_terms' => '1',
            ])
            ->assertRedirect(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]));

        $this->assertTrue(SubscriptionTerms::acceptedForPlan(SubscriptionPlans::ONE_MONTH));

        $this->actingAs($user)
            ->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk();
    }

    public function test_cannot_accept_terms_without_checkbox(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('subscribe.terms.accept', ['plan' => SubscriptionPlans::ONE_MONTH]), [])
            ->assertSessionHasErrors('accept_terms');

        $this->assertFalse(SubscriptionTerms::acceptedForPlan(SubscriptionPlans::ONE_MONTH));
    }

    public function test_terms_page_renders_content_from_admin_legal_page(): void
    {
        $user = User::factory()->create();

        LegalPage::query()
            ->where('slug', 'subscription-terms')
            ->update([
                'content' => '<p>Custom subscription terms from admin.</p>',
            ]);

        $this->actingAs($user)
            ->get(route('subscribe.terms', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertSee('Custom subscription terms from admin.', false);
    }

    public function test_terms_acceptance_is_invalidated_when_admin_updates_terms(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('subscribe.terms.accept', ['plan' => SubscriptionPlans::ONE_MONTH]), [
                'accept_terms' => '1',
            ])
            ->assertRedirect(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]));

        $this->assertTrue(SubscriptionTerms::acceptedForPlan(SubscriptionPlans::ONE_MONTH));

        $this->travel(2)->seconds();

        LegalPage::query()
            ->where('slug', 'subscription-terms')
            ->update([
                'content' => '<p>Updated subscription terms.</p>',
            ]);

        $this->assertFalse(SubscriptionTerms::acceptedForPlan(SubscriptionPlans::ONE_MONTH));

        $this->actingAs($user)
            ->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertRedirect(route('subscribe.terms', ['plan' => SubscriptionPlans::ONE_MONTH]));
    }

    public function test_terms_acceptance_does_not_apply_to_different_plan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('subscribe.terms.accept', ['plan' => SubscriptionPlans::ONE_WEEK]), [
                'accept_terms' => '1',
            ])
            ->assertRedirect(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_WEEK]));

        $this->actingAs($user)
            ->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertRedirect(route('subscribe.terms', ['plan' => SubscriptionPlans::ONE_MONTH]));
    }
}
