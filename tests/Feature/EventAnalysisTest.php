<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventAnalysis;
use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_persists_and_exports_expected_json_shape(): void
    {
        $eventId = 1152117365672713705;
        $tournament = Tournament::query()->create(['name' => 'Bundesliga']);
        $home = Team::query()->create([
            'name' => 'Union Berlin',
            'short_name' => 'UNI',
            'league' => 'BL',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'Augsburg',
            'short_name' => 'AUG',
            'league' => 'BL',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $analysis = EventAnalysis::query()->create([
            'event_id' => $eventId,
            'type' => 'STANDINGS_MOTIVATION',
            'strength' => 7,
            'event_name' => 'Union Berlin vs Augsburg',
            'likely_outcome' => EventAnalysis::LIKELY_OUTCOME_AWAY_WIN,
            'approximate_goals' => 2,
            'description' => 'Union Berlin are 12th with little to play for.',
            'home_motivation' => 2,
            'away_motivation' => 6,
            'home_class' => 4,
            'away_class' => 5,
            'influenced_by' => ['Freiburg vs RB Leipzig', 'Eintracht Frankfurt vs Stuttgart'],
            'influenced_by_event_ids' => ['537909020840964760', '595596105163812078'],
        ]);

        $this->assertTrue(EventAnalysis::isValidLikelyOutcome($analysis->likely_outcome));
        $this->assertFalse(EventAnalysis::isValidLikelyOutcome('INVALID'));
        $this->assertTrue(EventAnalysis::isValidStrength($analysis->strength));
        $this->assertFalse(EventAnalysis::isValidStrength(11));

        EventAnalysis::query()->create([
            'event_id' => $eventId,
            'type' => 'REVISED',
            'strength' => 0,
            'event_name' => 'Union Berlin vs Augsburg',
            'likely_outcome' => EventAnalysis::LIKELY_OUTCOME_DRAW,
            'approximate_goals' => 2,
            'description' => 'Second pass.',
            'home_motivation' => 3,
            'away_motivation' => 5,
            'home_class' => 4,
            'away_class' => 5,
        ]);

        $event = Event::query()->find($eventId);
        $this->assertCount(2, $event?->eventAnalyses ?? []);
        $this->assertTrue($event?->eventAnalyses->contains('id', $analysis->id));

        $this->assertSame([
            'eventId' => (string) $eventId,
            'type' => 'STANDINGS_MOTIVATION',
            'strength' => 7,
            'eventName' => 'Union Berlin vs Augsburg',
            'likely_outcome' => 'AWAY_WIN',
            'approximate_goals' => 2,
            'description' => 'Union Berlin are 12th with little to play for.',
            'home_motivation' => 2,
            'away_motivation' => 6,
            'home_class' => 4,
            'away_class' => 5,
            'influenced_by' => ['Freiburg vs RB Leipzig', 'Eintracht Frankfurt vs Stuttgart'],
            'influenced_by_event_ids' => ['537909020840964760', '595596105163812078'],
        ], $analysis->toExportArray());
    }
}
