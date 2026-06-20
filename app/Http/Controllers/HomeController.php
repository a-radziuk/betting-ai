<?php

namespace App\Http\Controllers;

use App\Services\HomepageCache;
use App\Support\HomepageTopUserMetrics;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomepageCache $homepageCache): View
    {
        return view('welcome', [
            'mainHtml' => $homepageCache->mainContentHtml(),
            'showHomePromocode' => ! auth()->user()?->hasActiveSeeTipsAccess(),
            'heroTopUserMetric' => HomepageTopUserMetrics::bestForHero(),
        ]);
    }
}
