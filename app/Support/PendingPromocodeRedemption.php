<?php

namespace App\Support;

use App\Models\User;
use App\Services\PromocodeRedemptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class PendingPromocodeRedemption
{
    public function __construct(
        private PromocodeRedemptionService $promocodeRedemptionService,
    ) {}

    public function applyToRedirect(User $user, RedirectResponse $redirect): RedirectResponse
    {
        $code = PendingPromocodeSession::pull();
        if ($code === null) {
            return $redirect;
        }

        try {
            $this->promocodeRedemptionService->redeem($user, $code);

            return $redirect->with(
                'status',
                $this->promocodeRedemptionService->successMessage($user->fresh()),
            );
        } catch (ValidationException $e) {
            return $redirect->withErrors($e->errors());
        }
    }
}
