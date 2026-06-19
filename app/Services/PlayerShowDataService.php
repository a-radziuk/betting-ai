<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBet;
use App\Support\PlayerResolvedBets;
use App\Support\PlayerWalletResultChart;

class PlayerShowDataService
{
    public const RESULT_CHART_RECENT_LIMIT = 30;

    /**
     * @return array{
     *     player: User,
     *     bets: \Illuminate\Contracts\Pagination\LengthAwarePaginator,
     *     resultChart: PlayerWalletResultChart,
     *     resolvedBetCount: int,
     *     wonBetCount: int,
     *     lostBetCount: int,
     *     voidBetCount: int,
     *     turnover: float,
     *     averageStake: float|null,
     *     totalResult: float,
     *     efficiencyPercent: float|null,
     *     efficiencyPercentAbsolute: float|null,
     *     pendingBetCount: int
     * }
     */
    public function get(User $user, int $page): array
    {
        $user->loadMissing('wallet');

        $resolvedBetCount = $this->resolvedBetCount($user);
        $resolvedAggregate = $this->resolvedBetsAggregate($user);

        $resultChart = $this->buildResultChart($user, self::RESULT_CHART_RECENT_LIMIT, $resolvedBetCount);
        $wonBetCount = (int) ($resolvedAggregate->won_count ?? 0);
        $lostBetCount = (int) ($resolvedAggregate->lost_count ?? 0);
        $voidBetCount = (int) ($resolvedAggregate->void_count ?? 0);
        $turnover = (float) ($resolvedAggregate->turnover ?? 0);
        $averageStake = $resolvedBetCount > 0 ? $turnover / $resolvedBetCount : null;
        $totalResult = (float) $user->wallet->total_result;
        $efficiencyPercent = $turnover > 0.000001
            ? ($totalResult / $turnover) * 100
            : null;

        $startBalance = (float) $user->wallet->start_balance;
        $efficiencyPercentAbsolute = $startBalance > 0.000001
            ? ($totalResult / $startBalance) * 100
            : null;

        $pendingBetCount = UserBet::query()
            ->where('user_id', $user->id)
            ->where('status', UserBet::STATUS_PENDING)
            ->count();

        $bets = PlayerResolvedBets::listingQuery($user)
            ->paginate(20, ['*'], 'page', $page)
            ->withQueryString();

        return [
            'player' => $user,
            'bets' => $bets,
            'resultChart' => $resultChart,
            'resolvedBetCount' => $resolvedBetCount,
            'wonBetCount' => $wonBetCount,
            'lostBetCount' => $lostBetCount,
            'voidBetCount' => $voidBetCount,
            'turnover' => $turnover,
            'averageStake' => $averageStake,
            'totalResult' => $totalResult,
            'efficiencyPercent' => $efficiencyPercent,
            'efficiencyPercentAbsolute' => $efficiencyPercentAbsolute,
            'pendingBetCount' => $pendingBetCount,
        ];
    }

    public function buildFullResultChart(User $user): PlayerWalletResultChart
    {
        return $this->buildResultChart(
            $user,
            null,
            $this->resolvedBetCount($user),
        );
    }

    public function buildResultChart(User $user, ?int $limit, ?int $resolvedBetCount = null): PlayerWalletResultChart
    {
        $resolvedBetCount ??= $this->resolvedBetCount($user);
        $chartValues = $this->resolvedBetChartValues($user, $limit);
        $startAtZero = $limit === null || $resolvedBetCount <= self::RESULT_CHART_RECENT_LIMIT;

        return PlayerWalletResultChart::fromValues($chartValues, startAtZero: $startAtZero);
    }

    public function resolvedBetCount(User $user): int
    {
        return (int) ($this->resolvedBetsAggregate($user)->bet_count ?? 0);
    }

    /**
     * @return list<float|int|string|null>
     */
    private function resolvedBetChartValues(User $user, ?int $limit): array
    {
        $query = UserBet::query()
            ->where('user_bets.user_id', $user->id)
            ->where('user_bets.status', '!=', UserBet::STATUS_PENDING)
            ->orderByDesc('user_bets.resolved_order')
            ->orderByDesc('user_bets.id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get(['user_bets.wallet_total_result', 'user_bets.resolved_order', 'user_bets.id'])
            ->sortBy([
                ['resolved_order', 'asc'],
                ['id', 'asc'],
            ])
            ->pluck('wallet_total_result')
            ->values()
            ->all();
    }

    private function resolvedBetsAggregate(User $user): UserBet
    {
        return UserBet::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', UserBet::STATUS_PENDING)
            ->selectRaw(
                'COUNT(*) as bet_count,
            COALESCE(SUM(stake), 0) as turnover,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as won_count,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as lost_count,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as void_count',
                [UserBet::STATUS_WON, UserBet::STATUS_LOST, UserBet::STATUS_VOID],
            )
            ->first();
    }
}
