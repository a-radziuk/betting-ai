<?php

namespace App\PayWithMetamask\Services;

use App\Models\MetamaskPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MetamaskPaymentService
{
    /**
     * @param  array<string, mixed>  $paymentPayload
     */
    public function approveFromWebhook(MetamaskPayment $payment, array $paymentPayload): bool
    {
        return (bool) DB::transaction(function () use ($payment, $paymentPayload): bool {
            $locked = MetamaskPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $locked->isPending()) {
                return false;
            }

            return $this->activateSubscription($locked, $paymentPayload);
        });
    }

    /**
     * @param  array<string, mixed>  $paymentPayload
     */
    public function markPendingAdminReview(MetamaskPayment $payment, array $paymentPayload): bool
    {
        if (! $payment->isPending()) {
            return false;
        }

        $payment->update([
            'status' => MetamaskPayment::STATUS_PENDING_ADMIN_REVIEW,
            'payment_payload' => $paymentPayload,
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $paymentPayload
     */
    private function activateSubscription(MetamaskPayment $payment, array $paymentPayload): bool
    {
        $user = User::query()->whereKey($payment->user_id)->lockForUpdate()->first();
        if ($user === null) {
            return false;
        }

        $user->extendSeeTipsAccessForPlan($payment->plan_id);

        $payment->update([
            'status' => MetamaskPayment::STATUS_APPROVED,
            'payment_payload' => $paymentPayload,
            'approved_at' => now(),
        ]);

        return true;
    }
}
