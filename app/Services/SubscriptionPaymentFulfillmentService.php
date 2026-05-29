<?php

namespace App\Services;

use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\StripeConfig;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class SubscriptionPaymentFulfillmentService
{
    public function fulfillByPaymentIntentId(string $paymentIntentId): bool
    {
        $record = SubscriptionPayment::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if ($record === null || $record->isFulfilled()) {
            return false;
        }

        if (! $this->paymentIntentSucceeded($paymentIntentId)) {
            return false;
        }

        return $this->fulfillRecord($record);
    }

    public function fulfillFromWebhookPaymentIntent(object $intent): bool
    {
        if (($intent->status ?? null) !== 'succeeded') {
            return false;
        }

        $record = SubscriptionPayment::query()
            ->where('stripe_payment_intent_id', $intent->id)
            ->first();

        if ($record === null || $record->isFulfilled()) {
            return false;
        }

        $metadataUserId = self::metadataValue($intent, 'user_id');
        $metadataPlanId = self::metadataValue($intent, 'plan_id');

        if ($metadataUserId !== (string) $record->user_id || $metadataPlanId !== $record->plan_id) {
            return false;
        }

        return $this->fulfillRecord($record);
    }

    private function paymentIntentSucceeded(string $paymentIntentId): bool
    {
        if (! StripeConfig::isConfigured()) {
            return false;
        }

        try {
            Stripe::setApiKey(config('stripe.secret'));
            $intent = PaymentIntent::retrieve($paymentIntentId);

            return $intent->status === 'succeeded';
        } catch (ApiErrorException) {
            return false;
        }
    }

    private static function metadataValue(object $intent, string $key): string
    {
        $metadata = $intent->metadata ?? null;
        if ($metadata === null) {
            return '';
        }

        if (is_array($metadata)) {
            return (string) ($metadata[$key] ?? '');
        }

        return (string) ($metadata->{$key} ?? '');
    }

    private function fulfillRecord(SubscriptionPayment $record): bool
    {
        return (bool) DB::transaction(function () use ($record): bool {
            $locked = SubscriptionPayment::query()
                ->whereKey($record->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->isFulfilled()) {
                return false;
            }

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->first();
            if ($user === null) {
                return false;
            }

            $user->extendSeeTipsAccessForPlan($locked->plan_id);

            $locked->update([
                'status' => SubscriptionPayment::STATUS_FULFILLED,
                'fulfilled_at' => now(),
            ]);

            return true;
        });
    }
}
