<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBet;
use App\Models\UserMetric;
use App\Support\PlayerResolvedBets;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UserMetricsService
{
    private const WINNING_STREAK_MIN_LENGTH = 6;

    /**
     * @return array{deleted: int, users_processed: int, metrics_created: int}
     */
    public function generate(?Carbon $now = null): array
    {
        $now ??= now();

        $deleted = UserMetric::query()
            ->where('created_at', '<', $now->copy()->subDay())
            ->delete();

        $usersProcessed = 0;
        $metricsCreated = 0;

        User::query()
            ->where('is_metrics_available', true)
            ->dueForMetricsUpdate($now)
            ->with('wallet')
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$usersProcessed, &$metricsCreated, $now): void {
                foreach ($users as $user) {
                    $metrics = $this->buildMetricsForUser($user);

                    if ($metrics === []) {
                        continue;
                    }

                    $usersProcessed++;
                    $timestamp = $now->toDateTimeString();

                    foreach ($metrics as $metric) {
                        UserMetric::query()->create([
                            ...$metric,
                            'user_id' => $user->id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]);
                        $metricsCreated++;
                    }

                    $user->forceFill([
                        'metrics_updated_at' => $now,
                    ])->save();
                }
            });

        return [
            'deleted' => $deleted,
            'users_processed' => $usersProcessed,
            'metrics_created' => $metricsCreated,
        ];
    }

    /**
     * @return list<array{type: string, amount: float, length: int|null, bets_stats: array{won: int, lost: int, drawn: int}|null}>
     */
    public function buildMetricsForUser(User $user): array
    {
        $metrics = [];

        $totalResult = (float) ($user->wallet?->total_result ?? 0);
        if ($totalResult > 0) {
            $metrics[] = $this->metric(
                UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
                $totalResult,
                betsStats: $this->betsStats($this->allResolvedBets($user)),
            );
        }

        foreach ([10, 20, 30] as $betCount) {
            $bets = $this->recentResolvedBets($user, $betCount);

            if ($bets->count() < $betCount) {
                continue;
            }

            $recentResult = round($bets->sum(
                static fn (UserBet $bet): float => PlayerResolvedBets::wonLostAmount($bet),
            ), 2);

            if ($recentResult <= 0) {
                continue;
            }

            $metrics[] = $this->metric(
                match ($betCount) {
                    10 => UserMetric::TYPE_LAST_10_POSITIVE,
                    20 => UserMetric::TYPE_LAST_20_POSITIVE,
                    30 => UserMetric::TYPE_LAST_30_POSITIVE,
                },
                $recentResult,
                betsStats: $this->betsStats($bets),
            );
        }

        $winningStreak = $this->winningStreak($user);
        if ($winningStreak !== null) {
            $metrics[] = $this->metric(
                UserMetric::TYPE_WINNING_STREAK,
                $winningStreak['amount'],
                $winningStreak['length'],
            );
        }

        return $metrics;
    }

    /**
     * @return Collection<int, UserBet>
     */
    private function allResolvedBets(User $user): Collection
    {
        return UserBet::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', UserBet::STATUS_PENDING)
            ->get();
    }

    /**
     * @param  Collection<int, UserBet>  $bets
     * @return array{won: int, lost: int, drawn: int}
     */
    private function betsStats(Collection $bets): array
    {
        return [
            'won' => $bets->where('status', UserBet::STATUS_WON)->count(),
            'lost' => $bets->where('status', UserBet::STATUS_LOST)->count(),
            'drawn' => $bets->whereIn('status', [UserBet::STATUS_VOID, UserBet::STATUS_CANCELLED])->count(),
        ];
    }

    /**
     * @return Collection<int, UserBet>
     */
    private function recentResolvedBets(User $user, int $limit): Collection
    {
        return UserBet::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', UserBet::STATUS_PENDING)
            ->orderByResolvedSettlementDesc()
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{length: int, amount: float}|null
     */
    private function winningStreak(User $user): ?array
    {
        $bets = UserBet::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', UserBet::STATUS_PENDING)
            ->orderByResolvedSettlementDesc()
            ->get();

        $length = 0;
        $amount = 0.0;

        foreach ($bets as $bet) {
            if ($bet->status !== UserBet::STATUS_WON) {
                break;
            }

            $length++;
            $amount += PlayerResolvedBets::wonLostAmount($bet);
        }

        if ($length <= 5) {
            return null;
        }

        return [
            'length' => $length,
            'amount' => round($amount, 2),
        ];
    }

    /**
     * @param  array{won: int, lost: int, drawn: int}|null  $betsStats
     * @return array{type: string, amount: float, length: int|null, bets_stats: array{won: int, lost: int, drawn: int}|null}
     */
    private function metric(
        string $type,
        float $amount,
        ?int $length = null,
        ?array $betsStats = null,
    ): array {
        return [
            'type' => $type,
            'amount' => round($amount, 2),
            'length' => $length,
            'bets_stats' => $betsStats,
        ];
    }
}
