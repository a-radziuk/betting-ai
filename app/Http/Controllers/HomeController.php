<?php

namespace App\Http\Controllers;

use App\Services\HomepageCache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomepageCache $homepageCache): View
    {
        return view('welcome', [
            'mainHtml' => $homepageCache->mainContentHtml(),
        ]);
    }
}
