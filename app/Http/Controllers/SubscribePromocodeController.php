<?php

namespace App\Http\Controllers;

use App\Services\PromocodeRedemptionService;
use App\Support\PendingPromocodeSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscribePromocodeController extends Controller
{
    public function store(Request $request, PromocodeRedemptionService $promocodeRedemptionService): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $user = $request->user();

        if ($user === null) {
            PendingPromocodeSession::store($validated['code']);

            return redirect()
                ->route('login')
                ->with('status', __('Sign in to apply your promocode.'));
        }

        $promocodeRedemptionService->redeem($user, $validated['code']);

        return redirect()
            ->back()
            ->with('status', $promocodeRedemptionService->successMessage($user->fresh()));
    }
}
