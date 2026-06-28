<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventPrediction;
use App\Models\Odd;
use App\Models\UserBet;
use App\Models\UserPredictionSubscription;
use App\Models\UserWallet;
use App\Services\PlaceBetService;
use Illuminate\Console\Command;

class PredictionsBetCommand extends Command
{
    protected $signature = 'predictions:bet';

    protected $description = 'Place bets for subscribers against up to three active event predictions';

    public function handle(PlaceBetService $placeBetService): int
    {
        $predictions = EventPrediction::query()
            ->active()
            ->with('event')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        if ($predictions->isEmpty()) {
            $this->components->info('No active event predictions found.');

            return self::SUCCESS;
        }

        foreach ($predictions as $prediction) {
            $event = $prediction->event;
            if ($event === null) {
                $this->components->warn("Prediction {$prediction->id}: event missing. Skipping.");

                continue;
            }

            if ($event->status !== Event::STATUS_SCHEDULED) {
                $this->components->warn("Prediction {$prediction->id}: event is not open for betting. Skipping.");

                continue;
            }

            $odd = Odd::query()->with('selection.market')->find($prediction->odds_id);
            if ($odd === null || $odd->selection === null || $odd->selection->market === null) {
                $this->components->warn("Prediction {$prediction->id}: odd {$prediction->odds_id} not found or incomplete. Skipping.");

                continue;
            }

            if ((int) $odd->selection->market->event_id !== (int) $prediction->event_id) {
                $this->components->warn("Prediction {$prediction->id}: odds_id does not match stored event. Skipping.");

                continue;
            }

            $subscriberIds = UserPredictionSubscription::query()
                ->where('prediction_type', $prediction->prediction_type)
                ->pluck('user_id');

            if ($subscriberIds->isEmpty()) {
                $this->components->info("Prediction {$prediction->id}: no subscribers for type {$prediction->prediction_type}.");

                continue;
            }

            foreach ($subscriberIds as $userId) {
                $exists = UserBet::query()
                    ->where('user_id', $userId)
                    ->where('event_id', $prediction->event_id)
                    ->where('prediction_type', $prediction->prediction_type)
                    ->exists();

                if ($exists) {
                    $this->components->warn("User {$userId}: already has a bet for event {$prediction->event_id} and prediction type {$prediction->prediction_type}. Skipping.");

                    continue;
                }

                $wallet = UserWallet::query()->where('user_id', $userId)->first();
                if ($wallet === null) {
                    $this->components->warn("User {$userId}: no wallet. Skipping.");

                    continue;
                }

                $startBalance = (float) $wallet->start_balance;
                $stakeRaw = $startBalance * ($prediction->bank_percentage / 100);
                $stakeStr = number_format($stakeRaw, 2, '.', '');

                if (bccomp($stakeStr, '0.01', 2) < 0) {
                    $this->components->warn("User {$userId}: computed stake {$stakeStr} is below minimum (check start_balance and bank_percentage). Skipping.");

                    continue;
                }

                $result = $placeBetService->placeBet(
                    $userId,
                    $prediction->odds_id,
                    $stakeStr,
                    $prediction->prediction_type,
                    $prediction->explanation,
                );

                if (! $result['ok']) {
                    $this->components->warn("User {$userId}: {$result['message']}");

                    continue;
                }

                $this->components->info($result['message']);
            }
        }

        $predictionIds = $predictions->pluck('id')->all();
        if ($predictionIds !== []) {
            EventPrediction::query()->whereIn('id', $predictionIds)->update(['is_active' => false]);
            $this->components->info('Marked '.count($predictionIds).' prediction(s) inactive.');
        }

        return self::SUCCESS;
    }
}
