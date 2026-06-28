<?php

namespace App\Http\Controllers;

use App\Models\UserBet;
use App\Support\PlayerResolvedBets;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminResolvedBetsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $bets = PlayerResolvedBets::listingQueryForAdmin($search)
            ->paginate(50)
            ->withQueryString();

        return view('admin.resolved-bets', [
            'bets' => $bets,
            'search' => $search,
            'timezone' => config('app.timezone'),
        ]);
    }
}
