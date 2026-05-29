<?php

namespace App\Http\Controllers;

use App\Support\SubscriptionPlans;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscribeController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        return view('subscribe', [
            'plans' => SubscriptionPlans::all(),
            'hasActiveSeeTips' => $user?->hasActiveSeeTipsAccess() ?? false,
            'seeTipsExpiresAt' => $user?->see_tips_expires_at,
        ]);
    }
}
