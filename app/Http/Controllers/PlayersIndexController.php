<?php

namespace App\Http\Controllers;

use App\Services\PlayersIndexCache;
use App\Support\PageSeo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayersIndexController extends Controller
{
    public function __invoke(Request $request, PlayersIndexCache $playersIndexCache): View
    {
        $page = max(1, $request->integer('page', 1));

        return view('players', [
            'mainHtml' => $playersIndexCache->mainContentHtml($page),
            'seo' => PageSeo::forPlayersIndex(),
        ]);
    }
}
