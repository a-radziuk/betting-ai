<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Tests\TestCase;

class EventImportFromUploadCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function sampleUploadPayload(Event $event, Team $home, Team $away): array
    {
        return [
            'exportDate' => '2026-05-19',
            'events' => [
                [
                    'id' => 99001,
                    'home_team_id' => $home->id,
                    'away_team_id' => $away->id,
                    'start_time' => now()->addDays(2)->toIso8601String(),
                    'status' => Event::STATUS_LIVE,
                    'created_at' => $event->created_at?->toIso8601String(),
                    'updated_at' => $event->updated_at?->toIso8601String(),
                    'score' => null,
                    'additional_data' => null,
                    'tournament_id' => null,
                    'markets' => [
                        [
                            'id' => 99020,
                            'event_id' => 99001,
                            'type' => Market::TYPE_OVER_UNDER,
                            'period' => Market::PERIOD_FULL_TIME,
                            'line' => 2.5,
                            'status' => Market::STATUS_OPEN,
                            'created_at' => now()->toIso8601String(),
                            'updated_at' => now()->toIso8601String(),
                            'is_supported_market' => true,
                            'selections' => [
                                [
                                    'id' => 99021,
                                    'market_id' => 99020,
                                    'name' => Selection::NAME_OVER,
                                    'participant_id' => null,
                                    'handicap' => '0.00',
                                    'created_at' => now()->format('Y-m-d H:i:s'),
                                    'handicap_home' => null,
                                    'odds' => [
                                        [
                                            'id' => 99022,
                                            'selection_id' => 99021,
                                            'odds' => 1.95,
                                            'probability' => 0.5,
                                            'is_active' => 1,
                                            'created_at' => now()->format('Y-m-d H:i:s'),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 99002,
                    'home_team_id' => $home->id,
                    'away_team_id' => $away->id,
                    'start_time' => now()->addDays(3)->toIso8601String(),
                    'status' => Event::STATUS_SCHEDULED,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                    'score' => null,
                    'additional_data' => null,
                    'tournament_id' => null,
                    'markets' => [],
                ],
            ],
        ];
    }

    public function test_imports_events_from_json_file(): void
    {
        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 99001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $oldMarket = Market::query()->create([
            'id' => 99010,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $oldSelection = Selection::query()->create([
            'id' => 99011,
            'market_id' => $oldMarket->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        Odd::query()->create([
            'id' => 99012,
            'selection_id' => $oldSelection->id,
            'odds' => 1.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $path = sys_get_temp_dir().'/event-upload-import-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode(
            $this->sampleUploadPayload($event, $home, $away),
            JSON_THROW_ON_ERROR,
        ));

        try {
            $exit = Artisan::call('event:import-from-upload', ['filepath' => $path]);
            $this->assertSame(0, $exit);
            $this->assertStringContainsString('Uploaded 2 event(s)', Artisan::output());

            $event->refresh();
            $this->assertSame(Event::STATUS_LIVE, $event->status);
            $this->assertNull(Market::query()->find(99010));
            $this->assertNull(Selection::query()->find(99011));
            $this->assertNull(Odd::query()->find(99012));

            $newMarket = Market::query()->find(99020);
            $this->assertNotNull($newMarket);
            $this->assertSame(Market::TYPE_OVER_UNDER, $newMarket->type);
            $this->assertNotNull(Selection::query()->find(99021));
            $this->assertNotNull(Odd::query()->find(99022));
            $this->assertNotNull(Event::query()->find(99002));
        } finally {
            @unlink($path);
        }
    }

    public function test_imports_export_all_for_upload_json_file(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19 12:00:00', config('app.timezone')));

        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        Event::query()->create([
            'id' => 88001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $exportPath = storage_path('app/export_2026-05-19.json');
        if (is_file($exportPath)) {
            unlink($exportPath);
        }

        $this->assertSame(0, Artisan::call('event:export-all-for-upload'));
        $this->assertFileExists($exportPath);

        Event::query()->whereKey(88001)->delete();

        $exit = Artisan::call('event:import-from-upload', ['filepath' => $exportPath]);
        $this->assertSame(0, $exit);
        $this->assertNotNull(Event::query()->find(88001));

        @unlink($exportPath);
        Carbon::setTestNow();
    }

    public function test_succeeds_with_empty_events_array(): void
    {
        $path = sys_get_temp_dir().'/event-upload-import-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode(['exportDate' => '2026-05-19', 'events' => []], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('event:import-from-upload', ['filepath' => $path]);
            $this->assertSame(0, $exit);
            $this->assertStringContainsString('Uploaded 0 event(s)', Artisan::output());
        } finally {
            @unlink($path);
        }
    }

    public function test_fails_when_file_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found or not readable');

        Artisan::call('event:import-from-upload', ['filepath' => '/this/path/does/not/exist-'.uniqid().'.json']);
    }

    public function test_fails_on_invalid_json(): void
    {
        $path = sys_get_temp_dir().'/event-upload-import-'.uniqid('', true).'.json';
        file_put_contents($path, '{not json');

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid JSON');

            Artisan::call('event:import-from-upload', ['filepath' => $path]);
        } finally {
            @unlink($path);
        }
    }

    public function test_imports_selection_value_from_upload_file(): void
    {
        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        Event::query()->create([
            'id' => 99050,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $path = sys_get_temp_dir().'/event-upload-import-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            'exportDate' => '2026-05-19',
            'events' => [
                [
                    'id' => 99050,
                    'home_team_id' => $home->id,
                    'away_team_id' => $away->id,
                    'start_time' => now()->addDay()->toIso8601String(),
                    'status' => Event::STATUS_SCHEDULED,
                    'markets' => [
                        [
                            'id' => 99051,
                            'event_id' => 99050,
                            'type' => Market::TYPE_TOTAL_ASIAN,
                            'period' => Market::PERIOD_FULL_TIME,
                            'line' => null,
                            'status' => Market::STATUS_OPEN,
                            'is_supported_market' => true,
                            'selections' => [
                                [
                                    'id' => 99052,
                                    'market_id' => 99051,
                                    'name' => Selection::NAME_OVER,
                                    'participant_id' => null,
                                    'handicap' => null,
                                    'value' => 2.5,
                                    'created_at' => now()->format('Y-m-d H:i:s'),
                                    'odds' => [
                                        [
                                            'id' => 99053,
                                            'selection_id' => 99052,
                                            'odds' => 1.85,
                                            'probability' => null,
                                            'is_active' => true,
                                            'created_at' => now()->format('Y-m-d H:i:s'),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('event:import-from-upload', ['filepath' => $path]);
            $this->assertSame(0, $exit);

            $selection = Selection::query()->find(99052);
            $this->assertNotNull($selection);
            $this->assertEquals(2.5, (float) $selection->value);
        } finally {
            @unlink($path);
        }
    }
}
