<?php

namespace App\Http\Controllers;

use App\Support\SubscriptionPlans;
use App\Support\SubscriptionTerms;
use App\Support\SubscriptionTermsContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscribeTermsController extends Controller
{
    public function show(string $plan): View|RedirectResponse
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

        if (SubscriptionTerms::acceptedForPlan($plan)) {
            return redirect()->route('subscribe.payment', ['plan' => $plan]);
        }

        return view('subscribe-terms', [
            'plan' => $planDetails,
            'termsContent' => SubscriptionTermsContent::renderedContent(),
        ]);
    }

    public function store(Request $request, string $plan): RedirectResponse
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

        $request->validate([
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => __('You must agree to the Terms and Conditions to continue.'),
        ]);

        SubscriptionTerms::accept($plan);

        return redirect()->route('subscribe.payment', ['plan' => $plan]);
    }
}
