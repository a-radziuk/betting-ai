<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PlayerShowCache;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerShowController extends Controller
{
    public function __invoke(Request $request, User $user, PlayerShowCache $playerShowCache): View
    {
        $page = max(1, $request->integer('page', 1));

        return view('player-stats', [
            'mainHtml' => $playerShowCache->mainContentHtml($user, $page),
        ]);
    }
}
