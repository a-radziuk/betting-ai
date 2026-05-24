<?php

namespace App\Http\Controllers;

use App\Models\UserBet;
use App\Services\UserBetDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminUserBetsController extends Controller
{
    public function index(): View
    {
        $bets = UserBet::query()
            ->active()
            ->join('events', 'events.id', '=', 'user_bets.event_id')
            ->select('user_bets.*')
            ->with([
                'user',
                'event.homeTeam',
                'event.awayTeam',
                'event.tournament',
                'odd.selection.market',
            ])
            ->orderBy('events.start_time')
            ->orderBy('user_bets.id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.user-bets', [
            'bets' => $bets,
            'timezone' => config('app.timezone'),
        ]);
    }

    public function destroy(UserBet $bet, UserBetDeletionService $deletionService): RedirectResponse
    {
        if ($bet->status !== UserBet::STATUS_PENDING) {
            return redirect()
                ->route('admin.user-bets')
                ->with('status', __('Only pending bets can be deleted here.'));
        }

        $result = $deletionService->deleteAndRevertWallet($bet);

        return redirect()
            ->route('admin.user-bets')
            ->with('status', $result['message']);
    }
}
