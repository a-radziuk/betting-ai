<?php

namespace App\Http\Controllers;

use App\Models\Promocode;
use App\Services\PromocodeRedemptionService;
use App\Support\PendingPromocodeSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TelegramPromocodeLandingController extends Controller
{
    public function __invoke(
        string $promocode,
        PromocodeRedemptionService $promocodeRedemptionService,
    ): RedirectResponse {
        $record = Promocode::query()
            ->where('code', $promocode)
            ->firstOrFail();

        if ($record->isUsed()) {
            return redirect()
                ->route('register')
                ->withErrors([
                    'code' => __('This promocode has already been used.'),
                ]);
        }

        $user = auth()->user();

        if ($user !== null && $record->hasBeenUsedByUser($user)) {
            return redirect()
                ->route('dashboard')
                ->withErrors([
                    'code' => __('You have already used this promocode.'),
                ]);
        }

        if ($user !== null) {
            try {
                $promocodeRedemptionService->redeem($user, $record->code);

                return redirect()
                    ->route('dashboard')
                    ->with('status', $promocodeRedemptionService->successMessage($user->fresh()));
            } catch (ValidationException $e) {
                return redirect()
                    ->route('dashboard')
                    ->withErrors($e->errors());
            }
        }

        PendingPromocodeSession::store($record->code);

        return redirect()
            ->route('register')
            ->with('status', __('Register to apply your promocode.'));
    }
}
