<?php

namespace App\Services;

use App\Models\SimpleCryptoPayment;
use App\Models\User;
use App\Support\SimpleCryptoPaymentCode;
use App\Support\SimpleCryptoWallets;
use App\Support\SubscriptionPlans;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SimpleCryptoPaymentService
{
    public function findOrCreatePayment(User $user, string $planId, string $walletKey): SimpleCryptoPayment
    {
        $plan = SubscriptionPlans::find($planId);
        if ($plan === null) {
            throw new InvalidArgumentException('Invalid subscription plan.');
        }

        $wallet = SimpleCryptoWallets::find($walletKey);
        if ($wallet === null) {
            throw new InvalidArgumentException('Invalid or unavailable crypto wallet.');
        }

        $existing = SimpleCryptoPayment::query()
            ->where('user_id', $user->id)
            ->where('plan_id', $planId)
            ->where('wallet_key', $walletKey)
            ->whereIn('status', [
                SimpleCryptoPayment::STATUS_AWAITING_PAYMENT,
                SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
                SimpleCryptoPayment::STATUS_PENDING_ADMIN_REVIEW,
            ])
            ->latest('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => $planId,
            'wallet_key' => $wallet['key'],
            'wallet_label' => $wallet['label'],
            'wallet_address' => $wallet['address'],
            'payment_code' => SimpleCryptoPaymentCode::generate(),
            'amount_cents' => SubscriptionPlans::amountInMinorUnits($planId),
            'currency' => strtolower(SubscriptionPlans::currency()),
            'status' => SimpleCryptoPayment::STATUS_AWAITING_PAYMENT,
        ]);
    }

    public function markPaid(SimpleCryptoPayment $payment): bool
    {
        if (! $payment->isAwaitingPayment()) {
            return false;
        }

        $payment->update([
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);

        return true;
    }

    public function approve(SimpleCryptoPayment $payment, User $admin): bool
    {
        return (bool) DB::transaction(function () use ($payment, $admin): bool {
            $locked = SimpleCryptoPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $locked->isApprovableByAdmin()) {
                return false;
            }

            return $this->activateSubscription($locked, $admin->id);
        });
    }

    /**
     * @param  array<string, mixed>  $paymentPayload
     */
    public function approveFromWebhook(SimpleCryptoPayment $payment, array $paymentPayload): bool
    {
        return (bool) DB::transaction(function () use ($payment, $paymentPayload): bool {
            $locked = SimpleCryptoPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $locked->isPendingApproval()) {
                return false;
            }

            return $this->activateSubscription($locked, null, $paymentPayload);
        });
    }

    /**
     * @param  array<string, mixed>  $paymentPayload
     */
    public function markPendingAdminReview(SimpleCryptoPayment $payment, array $paymentPayload): bool
    {
        if (! $payment->isPendingApproval()) {
            return false;
        }

        $payment->update([
            'status' => SimpleCryptoPayment::STATUS_PENDING_ADMIN_REVIEW,
            'paid_at' => $payment->paid_at ?? now(),
            'payment_payload' => $paymentPayload,
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $paymentPayload
     */
    private function activateSubscription(
        SimpleCryptoPayment $payment,
        ?int $approvedByUserId,
        ?array $paymentPayload = null,
    ): bool {
        $user = User::query()->whereKey($payment->user_id)->lockForUpdate()->first();
        if ($user === null) {
            return false;
        }

        $user->extendSeeTipsAccessForPlan($payment->plan_id);

        $attributes = [
            'status' => SimpleCryptoPayment::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $approvedByUserId,
            'paid_at' => $payment->paid_at ?? now(),
        ];

        if ($paymentPayload !== null) {
            $attributes['payment_payload'] = $paymentPayload;
        }

        $payment->update($attributes);

        return true;
    }
}
