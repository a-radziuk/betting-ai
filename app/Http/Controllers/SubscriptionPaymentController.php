<?php

namespace App\Http\Controllers;

use App\Support\StripeConfig;
use App\Support\SubscriptionPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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

        $user = Auth::user();
        if ($user !== null && $user->hasActiveSeeTipsAccess()) {
            return redirect()
                ->route('subscribe')
                ->with('status', __('You already have access to tips.'));
        }

        $stripeFeatureEnabled = feature('subscription_stripe_payments');
        $stripeReady = $stripeFeatureEnabled && StripeConfig::isConfigured();

        return view('subscribe-payment', [
            'plan' => $planDetails,
            'stripeFeatureEnabled' => $stripeFeatureEnabled,
            'stripeReady' => $stripeReady,
            'stripePublishableKey' => config('stripe.key'),
        ]);
    }
}
