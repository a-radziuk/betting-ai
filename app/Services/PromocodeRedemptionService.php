<?php

namespace App\Services;

use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PromocodeRedemptionService
{
    public function __construct(
        private readonly ReferralPromocodeService $referralPromocodes,
    ) {}

    public function redeem(User $user, string $code): Promocode
    {
        $normalizedCode = Strtoupper(trim($code));

        if ($normalizedCode === '') {
            throw ValidationException::withMessages([
                'code' => __('Enter a promocode.'),
            ]);
        }

        return DB::transaction(function () use ($user, $normalizedCode): Promocode {
            $promocode = Promocode::query()
                ->where('code', $normalizedCode)
                ->lockForUpdate()
                ->first();

            if ($promocode === null) {
                throw ValidationException::withMessages([
                    'code' => __('This promocode is not valid.'),
                ]);
            }

            if ($promocode->isMultiple()) {
                if ($promocode->redemptions()->where('used_by_user_id', $user->id)->exists()) {
                    throw ValidationException::withMessages([
                        'code' => __('You have already used this promocode.'),
                    ]);
                }
            } elseif ($promocode->isUsed()) {
                throw ValidationException::withMessages([
                    'code' => __('This promocode has already been used.'),
                ]);
            }

            $this->referralPromocodes->assertNotSelfReferral($promocode, $user);

            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($this->userHasRedeemedAnyPromocode($lockedUser)) {
                throw ValidationException::withMessages([
                    'code' => __('You have already applied a promocode to your account.'),
                ]);
            }

            $lockedUser->extendSeeTipsAccessForDays($promocode->days);

            if ($promocode->isMultiple()) {
                PromocodeRedemption::query()->create([
                    'promocode_id' => $promocode->id,
                    'used_by_user_id' => $lockedUser->id,
                    'used_at' => now(),
                ]);
            } else {
                $promocode->update([
                    'used_at' => now(),
                    'used_by_user_id' => $lockedUser->id,
                ]);
            }

            if ($promocode->owner_user_id !== null) {
                $referrer = User::query()
                    ->whereKey($promocode->owner_user_id)
                    ->lockForUpdate()
                    ->first();

                if ($referrer !== null) {
                    $referrer->extendSeeTipsAccessForDays($this->referralPromocodes->referrerBonusDays());
                }
            }

            return $promocode->fresh();
        });
    }

    public function successMessage(User $user): string
    {
        return __('Promocode applied. Tips access is active until :date.', [
            'date' => $user->see_tips_expires_at
                ?->timezone(config('app.timezone'))
                ->format('Y-m-d H:i'),
        ]);
    }

    private function userHasRedeemedAnyPromocode(User $user): bool
    {
        if (Promocode::query()->where('used_by_user_id', $user->id)->exists()) {
            return true;
        }

        return PromocodeRedemption::query()
            ->where('used_by_user_id', $user->id)
            ->exists();
    }
}
