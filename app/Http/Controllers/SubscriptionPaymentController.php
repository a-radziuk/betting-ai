<?php

namespace App\Http\Controllers;

use App\Support\SubscriptionPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionPaymentController extends Controller
{
    public function __invoke(string $plan): View|RedirectResponse
    {
        $planDetails = SubscriptionPlans::find($plan);
        if ($planDetails === null) {
            return redirect()
                ->route('subscribe')
                ->withErrors(['plan' => __('This plan is not available.')]);
        }

        return view('subscribe-payment', [
            'plan' => $planDetails,
        ]);
    }
}
