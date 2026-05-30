<?php

namespace App\Services;

use App\Models\SimpleCryptoPayment;
use App\Support\SubscriptionPlans;

class SimpleCryptoPaymentTelegramMessage
{
    public function build(SimpleCryptoPayment $payment): string
    {
        $payment->loadMissing('user');

        $user = $payment->user;
        $plan = SubscriptionPlans::find($payment->plan_id);
        $planLabel = $plan['name'] ?? $payment->plan_id;
        $amount = number_format($payment->amount_cents / 100, 2).' '.strtoupper($payment->currency);
        $paidAt = $payment->paid_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—';
        $adminUrl = route('admin.simple-crypto-payments', absolute: true);

        $lines = [
            'Crypto payment — user marked as paid',
            '',
            'Payment ID: '.$payment->id,
            'Payment code: '.$payment->payment_code,
            'Status: '.$payment->status,
            '',
            'User: '.($user?->name ?? '—'),
            'Email: '.($user?->email ?? '—'),
            'User ID: '.$payment->user_id,
            '',
            'Plan: '.$planLabel.' ('.$payment->plan_id.')',
            'Amount: '.$amount,
            '',
            'Wallet: '.$payment->wallet_label,
            'Address: '.$payment->wallet_address,
            '',
            'Paid at: '.$paidAt,
            'Admin: '.$adminUrl,
        ];

        return implode("\n", $lines);
    }
}
