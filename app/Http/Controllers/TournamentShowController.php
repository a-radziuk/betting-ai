<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\TournamentShowCache;
use App\Support\PageSeo;
use Illuminate\View\View;

class TournamentShowController extends Controller
{
    public function __invoke(Tournament $tournament, TournamentShowCache $tournamentShowCache): View
    {
        return view('tournament-standings', [
            'tournament' => $tournament,
            'mainHtml' => $tournamentShowCache->mainContentHtml($tournament),
            'seo' => PageSeo::forTournamentShow($tournament),
        ]);
    }
}
