<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PlayersIndexDataService
{
    /**
     * @return array{players: LengthAwarePaginator}
     */
    public function get(int $page): array
    {
        $players = User::query()
            ->leftJoin('user_wallets', 'user_wallets.user_id', '=', 'users.id')
            ->whereExists(function ($q): void {
                $q->selectRaw('1')
                    ->from('user_bets')
                    ->whereColumn('user_bets.user_id', 'users.id');
            })
            ->orderByDesc(DB::raw('COALESCE(user_wallets.total_result, 0)'))
            ->select([
                'users.id',
                'users.name',
                DB::raw('COALESCE(user_wallets.balance, 0) as wallet_balance'),
                DB::raw('COALESCE(user_wallets.amount_in_play, 0) as wallet_amount_in_play'),
                DB::raw('COALESCE(user_wallets.total_result, 0) as wallet_total_result'),
                DB::raw("COALESCE(user_wallets.currency, 'EUR') as wallet_currency"),
            ])
            ->with([
                'bets' => UserBet::eagerLoadRecentResolved(),
            ])
            ->paginate(20, ['*'], 'page', $page)
            ->withQueryString();

        return compact('players');
    }
}
