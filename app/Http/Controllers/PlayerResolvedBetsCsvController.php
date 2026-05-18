<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PlayerResolvedBets;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlayerResolvedBetsCsvController extends Controller
{
    public function __invoke(User $user): StreamedResponse
    {
        $bets = PlayerResolvedBets::allForListing($user);

        $filename = sprintf('player-%d-resolved-bets.csv', $user->id);

        return response()->streamDownload(
            function () use ($bets): void {
                $handle = fopen('php://output', 'w');
                if ($handle === false) {
                    return;
                }

                fputcsv($handle, PlayerResolvedBets::csvHeaders());

                foreach ($bets as $bet) {
                    fputcsv($handle, PlayerResolvedBets::csvRow($bet));
                }

                fclose($handle);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}
