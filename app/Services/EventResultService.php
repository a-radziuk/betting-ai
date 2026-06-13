<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventResult;
use App\Models\Market;
use App\Models\Selection;
use App\Models\UserBet;
use App\Models\UserWallet;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EventResultService
{
    /**
     * @param  array<string, mixed>  $additionalData
     * @return array{ok: bool, message: string}
     */
    public function applyEventResult(int|string $eventId, string $result, array $additionalData): array
    {
        try {
            $fullTime = $this->parseScoreString($result);
        } catch (InvalidArgumentException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        $event = Event::query()->find($eventId);
        if ($event === null) {
            return ['ok' => false, 'message' => 'Event not found.'];
        }

        return DB::transaction(function () use ($event, $eventId, $result, $additionalData, $fullTime): array {
            Event::query()->whereKey($eventId)->update([
                'score' => $result,
                'additional_data' => $additionalData,
                'status' => Event::STATUS_PROCESSING,
            ]);

            $bets = UserBet::query()
                ->where('event_id', $eventId)
                ->where('status', UserBet::STATUS_PENDING)
                ->orderBy('id')
                ->with(['odd.selection.market'])
                ->get();

            foreach ($bets as $bet) {
                $this->settleBet($bet, $fullTime, $additionalData);
            }

            Event::query()->whereKey($eventId)->update([
                'status' => Event::STATUS_FINISHED,
            ]);

            $this->recordEventResult($event->fresh(), $result, $additionalData);

            return [
                'ok' => true,
                'message' => sprintf('Event settled. Processed %d pending bet(s).', $bets->count()),
            ];
        });
    }

    /**
     * @param  array{home: int, away: int}  $fullTime
     * @param  array<string, mixed>  $additionalData
     */
    private function settleBet(UserBet $bet, array $fullTime, array $additionalData): void
    {
        $previousBet = UserBet::query()
            ->where('user_id', $bet->user_id)
            ->whereKeyNot($bet->id)
            ->orderByDesc('resolved_order')
            ->orderByDesc('id')
            ->first();

        $resolvedOrder = $previousBet !== null
            ? $previousBet->resolved_order + 1
            : 1;

        $odd = $bet->odd;
        if ($odd === null || $odd->selection === null || $odd->selection->market === null) {
            $this->refundBet($bet);
            $this->applyResolvedOrder($bet, $resolvedOrder);

            return;
        }

        $market = $odd->selection->market;
        $selection = $odd->selection;

        if (! in_array($market->type, Market::SUPPORTED_TYPES, true)) {
            $this->refundBet($bet);
            $this->applyResolvedOrder($bet, $resolvedOrder);

            return;
        }

        $goals = $this->resolveGoalsForMarket($market, $fullTime, $additionalData);
        if ($goals === null) {
            $this->refundBet($bet);
            $this->applyResolvedOrder($bet, $resolvedOrder);

            return;
        }

        $outcome = $this->evaluateBetOutcome($market->type, $market, $selection, $goals);
        match ($outcome) {
            'win' => $this->winBet($bet),
            'refund' => $this->refundBet($bet),
            default => $this->loseBet($bet),
        };

        $this->applyResolvedOrder($bet, $resolvedOrder);
    }

    private function applyResolvedOrder(UserBet $bet, int $resolvedOrder): void
    {
        $bet->update(['resolved_order' => $resolvedOrder]);
    }

    /**
     * @param  array{home: int, away: int}  $goals
     * @return 'win'|'lose'|'refund'
     */
    private function evaluateBetOutcome(string $type, Market $market, Selection $selection, array $goals): string
    {
        $h = $goals['home'];
        $a = $goals['away'];
        $diff = $h - $a;
        $name = strtoupper(trim($selection->name));

        return match ($type) {
            Market::TYPE_MATCH_RESULT => $this->outcomeMatchResult($diff, $name),
            Market::TYPE_OVER_UNDER,
            Market::TYPE_OVER_UNDER_TOTAL_GOALS,
            Market::TYPE_OVER_UNDER_TOTAL_GOALS_EXTRA => $this->outcomeOverUnderTotal($h + $a, $market->line, $name),
            Market::TYPE_HOME_OVER_UNDER_TOTAL_GOALS => $this->outcomeOverUnderTotal($h, $market->line, $name),
            Market::TYPE_AWAY_OVER_UNDER_TOTAL_GOALS => $this->outcomeOverUnderTotal($a, $market->line, $name),
            Market::TYPE_BTTS => $this->outcomeBtts($h, $a, $name),
            Market::TYPE_HANDICAP => $this->outcomeHandicapThreeWay($h, $a, $selection),
            Market::TYPE_DOUBLE_CHANCE => $this->outcomeDoubleChance($diff, $name),
            Market::TYPE_CORRECT_SCORE => $this->outcomeCorrectScore($h, $a, $name),
            Market::TYPE_DRAW_NO_BET => $this->outcomeDrawNoBet($diff, $name),
            default => 'refund',
        };
    }

    /**
     * @return 'win'|'lose'|'refund'
     */
    private function outcomeMatchResult(int $diff, string $name): string
    {
        $actual = $diff > 0 ? 'HOME' : ($diff < 0 ? 'AWAY' : 'DRAW');
        $pick = $this->normalizeMatchResultPick($name);

        return $pick === $actual ? 'win' : 'lose';
    }

    private function normalizeMatchResultPick(string $name): string
    {
        $u = strtoupper(trim($name));
        if (in_array($u, ['1', 'HOME'], true)) {
            return 'HOME';
        }
        if (in_array($u, ['2', 'AWAY'], true)) {
            return 'AWAY';
        }
        if (in_array($u, ['X', 'DRAW'], true)) {
            return 'DRAW';
        }

        if (str_starts_with($u, 'HOME')) {
            return 'HOME';
        }
        if (str_starts_with($u, 'AWAY')) {
            return 'AWAY';
        }
        if (str_starts_with($u, 'DRAW')) {
            return 'DRAW';
        }

        return '';
    }

    /**
     * @return 'win'|'lose'|'refund'
     */
    private function outcomeOverUnderTotal(int $total, ?float $line, string $name): string
    {
        $l = $line ?? trim(str_replace(['OVER', 'UNDER'], '', $name));
        $l = (float)$l;

        $isOver = str_starts_with($name, 'OVER');
        $isUnder = str_starts_with($name, 'UNDER');
        if (! $isOver && ! $isUnder) {
            return 'lose';
        }

        if ($total > $l) {
            return $isOver ? 'win' : 'lose';
        }
        if ($total < $l) {
            return $isUnder ? 'win' : 'lose';
        }

        if (abs($l - round($l)) < 0.001) {
            return 'refund';
        }

        return 'lose';
    }

    /**
     * @return 'win'|'lose'
     */
    private function outcomeBtts(int $h, int $a, string $name): string
    {
        $both = $h > 0 && $a > 0;
        if (str_starts_with($name, 'YES')) {
            return $both ? 'win' : 'lose';
        }
        if (str_starts_with($name, 'NO')) {
            return ! $both ? 'win' : 'lose';
        }

        return 'lose';
    }

    /**
     * @return 'win'|'lose'|'refund'
     */
    private function outcomeHandicapThreeWay(int $h, int $a, Selection $selection): string
    {
        $name = strtoupper(trim($selection->name));
        $handicap = $selection->handicap;
        if ($handicap === null || ! is_numeric($handicap)) {
            return 'refund';
        }

        $handicap = (int) $handicap;

        if (str_starts_with($name, 'HOME')) {
            return $h + $handicap > $a ? 'win' : 'lose';
        }
        if (str_starts_with($name, 'DRAW')) {
            return $h + $handicap === $a ? 'win' : 'lose';
        }
        if (str_starts_with($name, 'AWAY')) {
            return $h + $handicap < $a ? 'win' : 'lose';
        }

        return 'lose';
    }

    /**
     * @return 'win'|'lose'
     */
    private function outcomeDoubleChance(int $diff, string $name): string
    {
        $u = strtoupper(preg_replace('/\s+/', '', $name) ?? '');
        $win = match ($u) {
            '1X', '1/X' => $diff >= 0,
            'X2' => $diff <= 0,
            '12' => $diff !== 0,
            default => false,
        };

        return $win ? 'win' : 'lose';
    }

    /**
     * @return 'win'|'lose'
     */
    private function outcomeCorrectScore(int $h, int $a, string $name): string
    {
        $actualKey = $h.'-'.$a;
        $normSel = strtoupper(str_replace([' ', '–', ':'], ['', '-', '-'], $name));

        if ($normSel === 'OTHER') {
            $known = Market::availableSelectionsByType()[Market::TYPE_CORRECT_SCORE];
            $listed = [];
            foreach ($known as $s) {
                if (strtoupper($s) === 'OTHER') {
                    continue;
                }
                $listed[] = strtoupper(str_replace([' ', ':'], ['', '-'], $s));
            }

            return in_array($actualKey, $listed, true) ? 'lose' : 'win';
        }

        return $normSel === $actualKey ? 'win' : 'lose';
    }

    /**
     * @return 'win'|'lose'|'refund'
     */
    private function outcomeDrawNoBet(int $diff, string $name): string
    {
        if ($diff === 0) {
            return 'refund';
        }

        $pick = $this->normalizeMatchResultPick($name);
        $winner = $diff > 0 ? 'HOME' : 'AWAY';

        return $pick === $winner ? 'win' : 'lose';
    }

    /**
     * @param  array{home: int, away: int}  $fullTime
     * @param  array<string, mixed>  $additionalData
     * @return array{home: int, away: int}|null
     */
    private function resolveGoalsForMarket(Market $market, array $fullTime, array $additionalData): ?array
    {
        if ($market->period === Market::PERIOD_HALF_TIME) {
            $half = $additionalData['firstHalf'] ?? null;
            if (! is_string($half) || trim($half) === '') {
                return null;
            }
            try {
                return $this->parseScoreString($half);
            } catch (InvalidArgumentException) {
                return null;
            }
        }

        return $fullTime;
    }

    /**
     * @return array{home: int, away: int}
     */
    private function parseScoreString(string $raw): array
    {
        $raw = trim($raw);
        if (! preg_match('/^(\d+)\s*[:-–]\s*(\d+)$/u', $raw, $m)) {
            throw new InvalidArgumentException('Invalid score format. Use e.g. 2:3 or 2-3.');
        }

        return [
            'home' => (int) $m[1],
            'away' => (int) $m[2],
        ];
    }

    /**
     * @param  array<string, mixed>  $additionalData
     */
    private function recordEventResult(Event $event, string $result, array $additionalData): void
    {
        if (EventResult::query()->where('event_id', $event->id)->exists()) {
            return;
        }

        $tournamentId = $event->tournament_id;
        if ($tournamentId === null) {
            $event->loadMissing(['homeTeam', 'awayTeam']);
            $tournamentId = $event->homeTeam?->tournament_id
                ?? $event->awayTeam?->tournament_id;
        }

        if ($tournamentId === null) {
            return;
        }

        $date = $event->start_time
            ? $event->start_time->timezone(config('app.timezone'))->toDateString()
            : now(config('app.timezone'))->toDateString();

        if (EventResult::query()
            ->where('home_team_id', $event->home_team_id)
            ->where('away_team_id', $event->away_team_id)
            ->whereDate('date', $date)
            ->exists()) {
            EventResult::query()
                ->where('home_team_id', $event->home_team_id)
                ->where('away_team_id', $event->away_team_id)
                ->whereDate('date', $date)
                ->whereNull('event_id')
                ->update([
                    'event_id' => $event->id,
                    'results' => $result,
                    'additional_data' => $additionalData !== [] ? $additionalData : null,
                ]);

            return;
        }

        EventResult::query()->create([
            'home_team_id' => $event->home_team_id,
            'away_team_id' => $event->away_team_id,
            'results' => $result,
            'additional_data' => $additionalData !== [] ? $additionalData : null,
            'date' => $date,
            'tournament_id' => $tournamentId,
            'event_id' => $event->id,
        ]);
    }

    private function winBet(UserBet $bet): void
    {
        $wallet = UserWallet::query()->where('user_id', $bet->user_id)->lockForUpdate()->first();
        if ($wallet !== null) {
            $betReturn = bcsub(
                number_format((float) $bet->potential_return, 2, '.', ''),
                number_format((float) $bet->stake, 2, '.', ''),
                2
            );
            $newBalance = bcadd(
                number_format((float) $wallet->balance, 2, '.', ''),
                number_format((float) $bet->potential_return, 2, '.', ''),
                2
            );
            $newAmountInPlay = bcsub(
                number_format((float) $wallet->amount_in_play, 2, '.', ''),
                number_format((float) $bet->stake, 2, '.', ''),
                2
            );
            $newTotalResult = bcadd(
                number_format((float) $wallet->total_result, 2, '.', ''),
                $betReturn,
                2
            );


            $wallet->update(['balance' => $newBalance, 'amount_in_play' => $newAmountInPlay, 'total_result' => $newTotalResult]);
        }

        $bet->update(['status' => UserBet::STATUS_WON, 'real_return' => $betReturn, 'wallet_total_result' => $newTotalResult]);
    }

    private function refundBet(UserBet $bet): void
    {
        $wallet = UserWallet::query()->where('user_id', $bet->user_id)->lockForUpdate()->first();
        if ($wallet !== null) {
            $newBalance = bcadd(
                number_format((float) $wallet->balance, 2, '.', ''),
                number_format((float) $bet->stake, 2, '.', ''),
                2
            );
            $newAmountInPlay = bcsub(
                number_format((float) $wallet->amount_in_play, 2, '.', ''),
                number_format((float) $bet->stake, 2, '.', ''),
                2
            );
            $wallet->update(['balance' => $newBalance, 'amount_in_play' => $newAmountInPlay]);
        }

        $bet->update(['status' => UserBet::STATUS_VOID, 'wallet_total_result' => $wallet->total_result]);
    }

    private function loseBet(UserBet $bet): void
    {
        $wallet = UserWallet::query()->where('user_id', $bet->user_id)->lockForUpdate()->first();
        if ($wallet !== null) {
            $newAmountInPlay = bcsub(
                number_format((float) $wallet->amount_in_play, 2, '.', ''),
                number_format((float) $bet->stake, 2, '.', ''),
                2
            );
            $newTotalResult = bcsub(
                number_format((float) $wallet->total_result, 2, '.', ''),
                number_format((float) $bet->stake, 2, '.', ''),
                2
            );
            $wallet->update(['amount_in_play' => $newAmountInPlay, 'total_result' => $newTotalResult]);
        }
        $bet->update([
            'status' => UserBet::STATUS_LOST,
            'wallet_total_result' => $newTotalResult,
            'real_return' => -$bet->stake
        ]);
    }
}
