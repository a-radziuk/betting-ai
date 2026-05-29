<?php

namespace App\Services;

use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\StripeConfig;
use App\Support\SubscriptionPlans;
use RuntimeException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeSubscriptionPaymentService
{
    /**
     * @param  array{id: string, price: string}  $plan
     * @return array{client_secret: string, payment_intent_id: string}
     */
    public function createPaymentIntent(User $user, array $plan): array
    {
        if (! StripeConfig::isConfigured()) {
            throw new RuntimeException('Stripe is not configured.');
        }

        Stripe::setApiKey(config('stripe.secret'));

        $currency = strtolower(SubscriptionPlans::currency());
        $amountCents = SubscriptionPlans::amountInMinorUnits($plan['id']);

        $intent = PaymentIntent::create([
            'amount' => $amountCents,
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan_id' => $plan['id'],
            ],
        ]);

        SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan['id'],
            'stripe_payment_intent_id' => $intent->id,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'status' => SubscriptionPayment::STATUS_PENDING,
        ]);

        if ($intent->client_secret === null) {
            throw new RuntimeException('Stripe did not return a client secret.');
        }

        return [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }
}
