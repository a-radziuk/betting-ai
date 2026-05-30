<?php

namespace Tests\Feature;

use App\Models\MetamaskPayment;
use App\Models\User;
use App\PayWithMetamask\Support\Config;
use App\Support\SubscriptionPlans;
use App\Support\SubscriptionTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayWithMetamaskTest extends TestCase
{
    use RefreshDatabase;

    private const TX_HASH = '0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';

    private const TX_HASH_USDC = '0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'features.pay_with_metamask' => true,
            'pay_with_metamask.ethereum_wallet' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'pay_with_metamask.usdt_contract_address' => '0xdAC17F958D2ee523a2206206994597C13D832831',
            'pay_with_metamask.usdc_contract_address' => '0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48',
            'pay_with_metamask.chain_id' => 1,
        ]);
    }

    private function acceptTerms(User $user, string $plan): void
    {
        SubscriptionTerms::accept($plan);
        $this->actingAs($user);
    }

    public function test_payment_page_shows_usdt_and_usdc_metamask_buttons(): void
    {
        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertSee('Pay with MetaMask', false)
            ->assertSee('Pay USDT with MetaMask', false)
            ->assertSee('Pay USDC with MetaMask', false)
            ->assertSee('data-usdt-contract="0xdAC17F958D2ee523a2206206994597C13D832831"', false)
            ->assertSee('data-usdc-contract="0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48"', false);
    }

    public function test_payment_page_shows_only_configured_stablecoins(): void
    {
        config(['pay_with_metamask.usdc_contract_address' => '']);

        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertSee('Pay USDT with MetaMask', false)
            ->assertDontSee('Pay USDC with MetaMask', false);
    }

    public function test_payment_page_hides_metamask_when_feature_disabled(): void
    {
        config(['features.pay_with_metamask' => false]);

        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertDontSee('Pay with MetaMask', false);
    }

    public function test_authenticated_user_can_record_usdt_transaction(): void
    {
        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->postJson(route('subscribe.payment.metamask', ['plan' => SubscriptionPlans::ONE_MONTH]), [
            'tx_hash' => self::TX_HASH,
            'token' => MetamaskPayment::TOKEN_USDT,
        ])
            ->assertOk()
            ->assertJsonPath('tx_hash', self::TX_HASH);

        $this->assertDatabaseHas('metamask_payments', [
            'user_id' => $user->id,
            'tx_hash' => self::TX_HASH,
            'token' => MetamaskPayment::TOKEN_USDT,
        ]);
    }

    public function test_authenticated_user_can_record_usdc_transaction(): void
    {
        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->postJson(route('subscribe.payment.metamask', ['plan' => SubscriptionPlans::ONE_MONTH]), [
            'tx_hash' => self::TX_HASH_USDC,
            'token' => MetamaskPayment::TOKEN_USDC,
        ])
            ->assertOk()
            ->assertJsonPath('tx_hash', self::TX_HASH_USDC);

        $this->assertDatabaseHas('metamask_payments', [
            'user_id' => $user->id,
            'tx_hash' => self::TX_HASH_USDC,
            'token' => MetamaskPayment::TOKEN_USDC,
        ]);
    }

    public function test_usdc_rejected_when_contract_not_configured(): void
    {
        config(['pay_with_metamask.usdc_contract_address' => '']);

        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->postJson(route('subscribe.payment.metamask', ['plan' => SubscriptionPlans::ONE_MONTH]), [
            'tx_hash' => self::TX_HASH_USDC,
            'token' => MetamaskPayment::TOKEN_USDC,
        ])->assertUnprocessable();
    }

    public function test_duplicate_tx_hash_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        MetamaskPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'tx_hash' => self::TX_HASH,
            'token' => MetamaskPayment::TOKEN_USDT,
            'amount_cents' => 2999,
            'recipient_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'status' => MetamaskPayment::STATUS_PENDING,
        ]);

        $this->postJson(route('subscribe.payment.metamask', ['plan' => SubscriptionPlans::ONE_MONTH]), [
            'tx_hash' => self::TX_HASH,
            'token' => MetamaskPayment::TOKEN_USDT,
        ])->assertUnprocessable();
    }

    public function test_eth_payment_requires_configured_wei_amount(): void
    {
        $user = User::factory()->create();
        $this->acceptTerms($user, SubscriptionPlans::ONE_MONTH);

        $this->postJson(route('subscribe.payment.metamask', ['plan' => SubscriptionPlans::ONE_MONTH]), [
            'tx_hash' => '0x1111111111111111111111111111111111111111111111111111111111111111',
            'token' => MetamaskPayment::TOKEN_ETH,
        ])->assertUnprocessable();
    }

    public function test_config_helper_reports_ready_state(): void
    {
        $this->assertTrue(Config::isReady());
    }

    public function test_guest_cannot_record_metamask_transaction(): void
    {
        $this->postJson(route('subscribe.payment.metamask', ['plan' => SubscriptionPlans::ONE_MONTH]), [
            'tx_hash' => self::TX_HASH,
            'token' => MetamaskPayment::TOKEN_USDT,
        ])->assertUnauthorized();
    }
}
