<?php

namespace App\Services;

use App\Models\Promocode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PromocodeRedemptionService
{
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

            if ($promocode->isUsed()) {
                throw ValidationException::withMessages([
                    'code' => __('This promocode has already been used.'),
                ]);
            }

            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedUser->extendSeeTipsAccessForDays($promocode->days);

            $promocode->update([
                'used_at' => now(),
                'used_by_user_id' => $lockedUser->id,
            ]);

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
}
