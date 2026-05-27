<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PlayerResolvedBets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlayerResolvedBetsCsvController extends Controller
{
    public function __invoke(User $user): StreamedResponse|RedirectResponse
    {
        if (! feature('player_stats_csv_download')) {
            abort(404);
        }

        $viewer = Auth::user();
        if ($viewer === null || ! $viewer->hasPrivelege(User::PRIVELEGE_SEE_TIPS)) {
            return redirect()->route('subscribe');
        }

        $bets = PlayerResolvedBets::allForCsv($user);
        $forSuperadmin = $viewer->isSuperadmin();

        $filename = sprintf('player-%d-resolved-bets.csv', $user->id);

        return response()->streamDownload(
            function () use ($bets, $forSuperadmin): void {
                $handle = fopen('php://output', 'w');
                if ($handle === false) {
                    return;
                }

                fputcsv($handle, PlayerResolvedBets::csvHeaders($forSuperadmin));

                foreach ($bets as $bet) {
                    fputcsv($handle, PlayerResolvedBets::csvRow($bet, $forSuperadmin));
                }

                fclose($handle);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
