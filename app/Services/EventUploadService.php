<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use Illuminate\Support\Facades\DB;

final class EventUploadService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(array $payload): int
    {
        $events = $payload['events'] ?? [];

        return DB::transaction(function () use ($events): int {
            $count = 0;

            foreach ($events as $eventRow) {
                if (! is_array($eventRow)) {
                    continue;
                }

                $this->importEvent($eventRow);
                $count++;
            }

            return $count;
        });
    }

    /**
     * @param  array<string, mixed>  $eventRow
     */
    private function importEvent(array $eventRow): void
    {
        $eventId = (int) $eventRow['id'];
        $markets = $eventRow['markets'] ?? [];
        unset($eventRow['markets']);

        $event = Event::query()->find($eventId);
        if ($event === null) {
            $event = new Event;
            $event->id = $eventId;
        }

        $event->forceFill($this->onlyKeys($eventRow, [
            'home_team_id',
            'away_team_id',
            'tournament_id',
            'start_time',
            'status',
            'score',
            'additional_data',
            'created_at',
            'updated_at',
        ]));
        $event->save();

        $this->deleteEventOddsTree($eventId);

        foreach ($markets as $marketRow) {
            if (! is_array($marketRow)) {
                continue;
            }

            $this->importMarket($eventId, $marketRow);
        }
    }

    private function deleteEventOddsTree(int $eventId): void
    {
        $marketIds = Market::query()
            ->where('event_id', $eventId)
            ->pluck('id');

        if ($marketIds->isEmpty()) {
            return;
        }

        $selectionIds = Selection::query()
            ->whereIn('market_id', $marketIds)
            ->pluck('id');

        if ($selectionIds->isNotEmpty()) {
            Odd::query()->whereIn('selection_id', $selectionIds)->delete();
            Selection::query()->whereIn('id', $selectionIds)->delete();
        }

        Market::query()->where('event_id', $eventId)->delete();
    }

    /**
     * @param  array<string, mixed>  $marketRow
     */
    private function importMarket(int $eventId, array $marketRow): void
    {
        $selections = $marketRow['selections'] ?? [];
        unset($marketRow['selections']);

        $market = new Market;
        $market->forceFill($this->onlyKeys(array_merge($marketRow, ['event_id' => $eventId]), [
            'id',
            'event_id',
            'type',
            'period',
            'line',
            'status',
            'is_supported_market',
            'created_at',
            'updated_at',
        ]));
        $market->save();

        foreach ($selections as $selectionRow) {
            if (! is_array($selectionRow)) {
                continue;
            }

            $this->importSelection((int) $market->id, $selectionRow);
        }
    }

    /**
     * @param  array<string, mixed>  $selectionRow
     */
    private function importSelection(int $marketId, array $selectionRow): void
    {
        $odds = $selectionRow['odds'] ?? [];
        unset($selectionRow['odds']);

        $selection = new Selection;
        $selection->forceFill($this->onlyKeys(array_merge($selectionRow, ['market_id' => $marketId]), [
            'id',
            'market_id',
            'name',
            'participant_id',
            'handicap',
            'handicap_home',
            'created_at',
        ]));
        $selection->save();

        foreach ($odds as $oddRow) {
            if (! is_array($oddRow)) {
                continue;
            }

            $this->importOdd((int) $selection->id, $oddRow);
        }
    }

    /**
     * @param  array<string, mixed>  $oddRow
     */
    private function importOdd(int $selectionId, array $oddRow): void
    {
        $odd = new Odd;
        $odd->forceFill($this->onlyKeys(array_merge($oddRow, ['selection_id' => $selectionId]), [
            'id',
            'selection_id',
            'odds',
            'probability',
            'is_active',
            'created_at',
        ]));
        $odd->save();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function onlyKeys(array $row, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                $out[$key] = $row[$key];
            }
        }

        return $out;
    }
}
