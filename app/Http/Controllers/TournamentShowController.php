<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\TournamentShowCache;
use Illuminate\View\View;

class TournamentShowController extends Controller
{
    public function __invoke(Tournament $tournament, TournamentShowCache $tournamentShowCache): View
    {
        $tournament->loadMissing('translations');

        return view('tournament-standings', [
            'tournament' => $tournament,
            'mainHtml' => $tournamentShowCache->mainContentHtml($tournament),
        ]);
    }
}
