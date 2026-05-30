<?php

namespace App\PayWithMetamask\Support;

use App\Support\SubscriptionPlans;
use InvalidArgumentException;

final class PlanPayment
{
    /**
     * @return array{
     *     plan_id: string,
     *     amount_cents: int,
     *     stablecoin_amount: string,
     *     eth_amount_wei: string|null,
     *     price_label: string
     * }
     */
    public static function forPlan(string $planId): array
    {
        $plan = SubscriptionPlans::find($planId);
        if ($plan === null) {
            throw new InvalidArgumentException('Invalid subscription plan.');
        }

        $amountCents = SubscriptionPlans::amountInMinorUnits($planId);
        $stablecoinAmount = (string) ($amountCents * 100);

        $ethWei = config('pay_with_metamask.eth_amount_wei_by_plan.'.$planId);
        $ethAmountWei = is_string($ethWei) && trim($ethWei) !== '' ? trim($ethWei) : null;

        return [
            'plan_id' => $planId,
            'amount_cents' => $amountCents,
            'stablecoin_amount' => $stablecoinAmount,
            'eth_amount_wei' => $ethAmountWei,
            'price_label' => $plan['price_label'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function presentationForPlan(string $planId): ?array
    {
        if (! Config::isReady()) {
            return null;
        }

        $payment = self::forPlan($planId);

        return [
            'recipient' => Config::ethereumWallet(),
            'usdt_contract' => Config::usdtContractAddress(),
            'usdc_contract' => Config::usdcContractAddress(),
            'chain_id' => Config::chainId(),
            'plan_id' => $payment['plan_id'],
            'amount_cents' => $payment['amount_cents'],
            'stablecoin_amount' => $payment['stablecoin_amount'],
            'eth_amount_wei' => $payment['eth_amount_wei'],
            'price_label' => $payment['price_label'],
            'record_url' => route('subscribe.payment.metamask', ['plan' => $planId]),
        ];
    }
}
