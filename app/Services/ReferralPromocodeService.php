<?php

namespace App\Services;

use App\Models\Promocode;
use App\Models\User;
use App\Support\PromocodeGenerator;
use Illuminate\Validation\ValidationException;

class ReferralPromocodeService
{
    public function issueForUser(User $user): ?Promocode
    {
        if (! $user->hasActiveSeeTipsAccess()) {
            return null;
        }

        $existing = Promocode::query()
            ->where('owner_user_id', $user->id)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $promocode = PromocodeGenerator::generateUnique($this->redeemDays(), $this->codePrefix());
        $promocode->update(['owner_user_id' => $user->id]);

        return $promocode->fresh();
    }

    public function shareLink(Promocode $promocode): string
    {
        return route('referral.promocode', [
            'promocode' => $promocode->code,
        ], absolute: true);
    }

    public function redeemDays(): int
    {
        return max(1, (int) config('referrals.redeem_days', 3));
    }

    public function referrerBonusDays(): int
    {
        return max(1, (int) config('referrals.referrer_bonus_days', 3));
    }

    public function codePrefix(): string
    {
        return (string) config('referrals.code_prefix', 'REF-');
    }

    public function assertNotSelfReferral(Promocode $promocode, User $user): void
    {
        if ($promocode->owner_user_id !== null && $promocode->owner_user_id === $user->id) {
            throw ValidationException::withMessages([
                'code' => __('You cannot use your own referral code.'),
            ]);
        }
    }
}
