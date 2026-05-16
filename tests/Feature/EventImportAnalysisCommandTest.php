<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventAnalysis;
use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Tests\TestCase;

class EventImportAnalysisCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedEvent(int $eventId, string $status = Event::STATUS_SCHEDULED): void
    {
        $tournament = Tournament::query()->create(['name' => 'Analysis League']);
        $home = Team::query()->create([
            'name' => 'Home',
            'short_name' => 'H',
            'league' => 'L',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'Away',
            'short_name' => 'A',
            'league' => 'L',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::now()->addDay(),
            'status' => $status,
        ]);
    }

    public function test_imports_analyses_from_json_file(): void
    {
        $eventId = 1152117365672713705;
        $this->seedEvent($eventId);

        $path = sys_get_temp_dir().'/event-analysis-import-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            [
                'eventId' => (string) $eventId,
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
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('event:import-analysis', ['filepath' => $path]);
            $this->assertSame(0, $exit);

            $analysis = EventAnalysis::query()->first();
            $this->assertNotNull($analysis);
            $this->assertSame($eventId, $analysis->event_id);
            $this->assertSame(EventAnalysis::TYPE_MANUAL, $analysis->type);
            $this->assertSame(EventAnalysis::STRENGTH_MAX, $analysis->strength);
            $this->assertSame('Union Berlin vs Augsburg', $analysis->event_name);
            $this->assertSame(EventAnalysis::LIKELY_OUTCOME_AWAY_WIN, $analysis->likely_outcome);
            $this->assertSame(2, $analysis->approximate_goals);
            $this->assertSame(2, $analysis->home_motivation);
            $this->assertSame(6, $analysis->away_motivation);
            $this->assertSame(['Freiburg vs RB Leipzig', 'Eintracht Frankfurt vs Stuttgart'], $analysis->influenced_by);
            $this->assertSame(['537909020840964760', '595596105163812078'], $analysis->influenced_by_event_ids);
        } finally {
            @unlink($path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleRow(int $eventId, string $eventName = 'A vs B'): array
    {
        return [
            'eventId' => (string) $eventId,
            'eventName' => $eventName,
            'likely_outcome' => 'DRAW',
            'approximate_goals' => 1,
            'description' => 'Test.',
            'home_motivation' => 5,
            'away_motivation' => 5,
            'home_class' => 5,
            'away_class' => 5,
        ];
    }

    public function test_skips_when_analysis_with_same_event_id_and_type_exists(): void
    {
        $eventId = 1152117365672713708;
        $this->seedEvent($eventId);

        $path = sys_get_temp_dir().'/event-analysis-import-dup-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            $this->sampleRow($eventId, 'First import'),
            $this->sampleRow($eventId, 'Second import'),
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('event:import-analysis', ['filepath' => $path]);
            $this->assertSame(0, $exit);
            $this->assertSame(1, EventAnalysis::query()->count());
            $this->assertSame('First import', EventAnalysis::query()->first()?->event_name);
        } finally {
            @unlink($path);
        }
    }

    public function test_imports_when_same_event_has_different_type(): void
    {
        $eventId = 1152117365672713709;
        $this->seedEvent($eventId);

        EventAnalysis::query()->create([
            'event_id' => $eventId,
            'type' => EventAnalysis::TYPE_GPT1,
            'strength' => 5,
            'event_name' => 'Existing GPT',
            'likely_outcome' => EventAnalysis::LIKELY_OUTCOME_HOME_WIN,
            'approximate_goals' => 1,
            'description' => 'GPT pass.',
            'home_motivation' => 5,
            'away_motivation' => 5,
            'home_class' => 5,
            'away_class' => 5,
        ]);

        $path = sys_get_temp_dir().'/event-analysis-import-other-type-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            $this->sampleRow($eventId, 'Manual import'),
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('event:import-analysis', ['filepath' => $path]);
            $this->assertSame(0, $exit);
            $this->assertSame(2, EventAnalysis::query()->count());
            $this->assertTrue(
                EventAnalysis::query()
                    ->where('event_id', $eventId)
                    ->where('type', EventAnalysis::TYPE_MANUAL)
                    ->exists(),
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_skips_finished_events(): void
    {
        $eventId = 1152117365672713706;
        $this->seedEvent($eventId, Event::STATUS_FINISHED);

        $path = sys_get_temp_dir().'/event-analysis-import-skip-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            [
                'eventId' => (string) $eventId,
                'eventName' => 'A vs B',
                'likely_outcome' => 'DRAW',
                'approximate_goals' => 1,
                'description' => 'Test.',
                'home_motivation' => 5,
                'away_motivation' => 5,
                'home_class' => 5,
                'away_class' => 5,
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('event:import-analysis', ['filepath' => $path]);
            $this->assertSame(0, $exit);
            $this->assertSame(0, EventAnalysis::query()->count());
        } finally {
            @unlink($path);
        }
    }

    public function test_skips_row_with_invalid_likely_outcome(): void
    {
        $eventId = 1152117365672713707;
        $this->seedEvent($eventId);

        $path = sys_get_temp_dir().'/event-analysis-import-invalid-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            [
                'eventId' => (string) $eventId,
                'eventName' => 'A vs B',
                'likely_outcome' => 'INVALID',
                'approximate_goals' => 1,
                'description' => 'Test.',
                'home_motivation' => 5,
                'away_motivation' => 5,
                'home_class' => 5,
                'away_class' => 5,
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('event:import-analysis', ['filepath' => $path]);
            $this->assertSame(0, $exit);
            $this->assertSame(0, EventAnalysis::query()->count());
        } finally {
            @unlink($path);
        }
    }

    public function test_throws_when_file_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found');

        Artisan::call('event:import-analysis', ['filepath' => '/this/path/does/not/exist-'.uniqid().'.json']);
    }

    public function test_throws_when_json_is_not_a_list(): void
    {
        $path = sys_get_temp_dir().'/event-analysis-import-obj-'.uniqid('', true).'.json';
        file_put_contents($path, '{"a":1}');

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('list');

            Artisan::call('event:import-analysis', ['filepath' => $path]);
        } finally {
            @unlink($path);
        }
    }
}
