<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PlayerResultTrendCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlayerResultTrendController extends Controller
{
    public function __invoke(User $user, PlayerResultTrendCache $playerResultTrendCache): View|RedirectResponse
    {
        $viewer = Auth::user();
        if ($viewer === null || ! $viewer->hasPrivelege(User::PRIVELEGE_SEE_TIPS)) {
            return redirect()->route('subscribe');
        }

        return view('player-result-trend', [
            'player' => $user,
            'mainHtml' => $playerResultTrendCache->mainContentHtml($user),
        ]);
    }
}
