<?php

namespace Tests\Feature;

use App\Models\SimpleCryptoPayment;
use App\Models\User;
use App\Support\SubscriptionPlans;
use App\Support\SubscriptionTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\NotifyCryptoWatcherOfPayment;
use App\Jobs\NotifySimpleCryptoPaymentPaid;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SimpleCryptoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'features.simple_crypto_payment' => true,
            'simple_crypto_payment.wallets.ethereum_usdt.visible' => true,
            'simple_crypto_payment.wallets.ethereum_usdt.address' => '0xETH1234567890abcdef',
            'simple_crypto_payment.wallets.tron_usdt.visible' => true,
            'simple_crypto_payment.wallets.tron_usdt.address' => 'TTron1234567890abcdef',
        ]);
    }

    private function acceptTerms(User $user, string $plan): void
    {
        SubscriptionTerms::accept($plan);
        $this->actingAs($user);
    }

    public function test_payment_page_lists_crypto_wallets_when_feature_enabled(): void
    {
        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertSee('Pay with crypto', false)
            ->assertSee('Ethereum USDT', false)
            ->assertSee('Tron USDT', false)
            ->assertSee(route('subscribe.payment.crypto', [
                'plan' => SubscriptionPlans::ONE_MONTH,
                'wallet' => 'ethereum_usdt',
            ]), false);
    }

    public function test_crypto_wallet_page_shows_address_and_payment_code(): void
    {
        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->get(route('subscribe.payment.crypto', [
            'plan' => SubscriptionPlans::ONE_MONTH,
            'wallet' => 'ethereum_usdt',
        ]))
            ->assertOk()
            ->assertSee('0xETH1234567890abcdef', false)
            ->assertSee('BETAI-', false)
            ->assertSee('I have paid', false);

        $this->assertDatabaseHas('simple_crypto_payments', [
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'wallet_key' => 'ethereum_usdt',
            'status' => SimpleCryptoPayment::STATUS_AWAITING_PAYMENT,
        ]);
    }

    public function test_i_have_paid_sets_pending_approval_status(): void
    {
        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->get(route('subscribe.payment.crypto', [
            'plan' => SubscriptionPlans::ONE_MONTH,
            'wallet' => 'tron_usdt',
        ]))->assertOk();

        $payment = SimpleCryptoPayment::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($payment);

        $this->post(route('subscribe.payment.crypto.paid', [
            'plan' => SubscriptionPlans::ONE_MONTH,
            'wallet' => 'tron_usdt',
        ]))
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame(SimpleCryptoPayment::STATUS_PENDING_APPROVAL, $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_i_have_paid_dispatches_telegram_notification_job(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->get(route('subscribe.payment.crypto', [
            'plan' => SubscriptionPlans::ONE_MONTH,
            'wallet' => 'ethereum_usdt',
        ]))->assertOk();

        $payment = SimpleCryptoPayment::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($payment);

        $this->post(route('subscribe.payment.crypto.paid', [
            'plan' => SubscriptionPlans::ONE_MONTH,
            'wallet' => 'ethereum_usdt',
        ]))->assertRedirect();

        Bus::assertDispatched(NotifySimpleCryptoPaymentPaid::class, function (NotifySimpleCryptoPaymentPaid $job) use ($payment): bool {
            return $job->simpleCryptoPaymentId === $payment->id;
        });
    }

    public function test_i_have_paid_dispatches_crypto_watcher_job(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->get(route('subscribe.payment.crypto', [
            'plan' => SubscriptionPlans::ONE_MONTH,
            'wallet' => 'tron_usdt',
        ]))->assertOk();

        $payment = SimpleCryptoPayment::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($payment);

        $this->post(route('subscribe.payment.crypto.paid', [
            'plan' => SubscriptionPlans::ONE_MONTH,
            'wallet' => 'tron_usdt',
        ]))->assertRedirect();

        Bus::assertDispatched(NotifyCryptoWatcherOfPayment::class, function (NotifyCryptoWatcherOfPayment $job) use ($payment): bool {
            return $job->simpleCryptoPaymentId === $payment->id;
        });
    }

    public function test_admin_can_approve_pending_payment_and_activate_subscription(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_superadmin' => true]);

        $payment = SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_WEEK,
            'wallet_key' => 'ethereum_usdt',
            'wallet_label' => 'Ethereum USDT',
            'wallet_address' => '0xETH1234567890abcdef',
            'payment_code' => 'BETAI-TESTCODE',
            'amount_cents' => 999,
            'currency' => 'eur',
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.simple-crypto-payments.approve', $payment))
            ->assertRedirect(route('admin.simple-crypto-payments'))
            ->assertSessionHas('status');

        $payment->refresh();
        $user->refresh();

        $this->assertSame(SimpleCryptoPayment::STATUS_APPROVED, $payment->status);
        $this->assertNotNull($payment->approved_at);
        $this->assertSame($admin->id, $payment->approved_by_user_id);
        $this->assertTrue($user->hasActiveSeeTipsAccess());
    }

    public function test_admin_index_lists_payment_code_and_user(): void
    {
        $user = User::factory()->create(['name' => 'Crypto Buyer', 'email' => 'buyer@example.com']);
        $admin = User::factory()->create(['is_superadmin' => true]);

        SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'wallet_key' => 'tron_usdt',
            'wallet_label' => 'Tron USDT',
            'wallet_address' => 'TTron1234567890abcdef',
            'payment_code' => 'BETAI-LISTME',
            'amount_cents' => 2999,
            'currency' => 'eur',
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.simple-crypto-payments'))
            ->assertOk()
            ->assertSee('BETAI-LISTME', false)
            ->assertSee('Crypto Buyer', false)
            ->assertSee('buyer@example.com', false)
            ->assertSee('Approve', false);
    }

    public function test_feature_flag_off_hides_crypto_from_payment_page(): void
    {
        config(['features.simple_crypto_payment' => false]);

        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertDontSee('Pay with crypto', false);
    }
}
