<?php

namespace App\Http\Controllers;

use App\Support\SubscriptionPlans;
use Illuminate\Http\RedirectResponse;

class SubscriptionPaymentCompleteController extends Controller
{
    public function __invoke(string $plan): RedirectResponse
    {
        $planDetails = SubscriptionPlans::find($plan);
        if ($planDetails === null) {
            return redirect()
                ->route('subscribe')
                ->withErrors(['plan' => __('This plan is not available.')]);
        }

        return redirect()
            ->route('subscribe')
            ->with('status', __('Thank you! Your subscription access will be active shortly.'));
    }
}
