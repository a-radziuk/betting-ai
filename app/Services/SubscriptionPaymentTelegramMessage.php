<?php

namespace App\Services;

use App\Models\SubscriptionPayment;
use App\Support\SubscriptionPlans;

class SubscriptionPaymentTelegramMessage
{
    public function build(SubscriptionPayment $payment): string
    {
        $payment->loadMissing('user');

        $user = $payment->user;
        $plan = SubscriptionPlans::find($payment->plan_id);
        $planLabel = $plan['name'] ?? $payment->plan_id;
        $amount = number_format($payment->amount_cents / 100, 2).' '.strtoupper($payment->currency);
        $fulfilledAt = $payment->fulfilled_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—';

        $lines = [
            'Stripe subscription payment fulfilled',
            '',
            'Payment ID: '.$payment->id,
            'Stripe Payment Intent: '.$payment->stripe_payment_intent_id,
            'Status: '.$payment->status,
            '',
            'User: '.($user?->name ?? '—'),
            'Email: '.($user?->email ?? '—'),
            'User ID: '.$payment->user_id,
            '',
            'Plan: '.$planLabel.' ('.$payment->plan_id.')',
            'Amount: '.$amount,
            '',
            'Fulfilled at: '.$fulfilledAt,
        ];

        return implode("\n", $lines);
    }
}
