<?php

namespace App\Services;

use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionPaymentFulfillmentService
{
    public function fulfillFromWebhookPaymentIntent(object $intent): bool
    {
        $paymentIntentId = $intent->id ?? null;

        if (($intent->status ?? null) !== 'succeeded') {
            Log::warning('Stripe fulfillment skipped: payment intent not succeeded', [
                'payment_intent_id' => $paymentIntentId,
                'status' => $intent->status ?? null,
            ]);

            return false;
        }

        $record = SubscriptionPayment::query()
            ->where('stripe_payment_intent_id', $intent->id)
            ->first();

        if ($record === null) {
            Log::warning('Stripe fulfillment skipped: subscription payment not found', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return false;
        }

        if ($record->isFulfilled()) {
            Log::info('Stripe fulfillment skipped: subscription payment already fulfilled', [
                'payment_intent_id' => $paymentIntentId,
                'subscription_payment_id' => $record->id,
            ]);

            return false;
        }

        $metadataUserId = self::metadataValue($intent, 'user_id');
        $metadataPlanId = self::metadataValue($intent, 'plan_id');

        if ($metadataUserId !== (string) $record->user_id || $metadataPlanId !== $record->plan_id) {
            Log::warning('Stripe fulfillment skipped: metadata mismatch', [
                'payment_intent_id' => $paymentIntentId,
                'subscription_payment_id' => $record->id,
                'record_user_id' => $record->user_id,
                'record_plan_id' => $record->plan_id,
                'metadata_user_id' => $metadataUserId,
                'metadata_plan_id' => $metadataPlanId,
            ]);

            return false;
        }

        $fulfilled = $this->fulfillRecord($record);

        if ($fulfilled) {
            Log::info('Stripe fulfillment completed', [
                'payment_intent_id' => $paymentIntentId,
                'subscription_payment_id' => $record->id,
                'user_id' => $record->user_id,
                'plan_id' => $record->plan_id,
            ]);
        } else {
            Log::warning('Stripe fulfillment failed during transaction', [
                'payment_intent_id' => $paymentIntentId,
                'subscription_payment_id' => $record->id,
            ]);
        }

        return $fulfilled;
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
                Log::warning('Stripe fulfillment aborted: user not found', [
                    'subscription_payment_id' => $locked->id,
                    'user_id' => $locked->user_id,
                ]);

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
