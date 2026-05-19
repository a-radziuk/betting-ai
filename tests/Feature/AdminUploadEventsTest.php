<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUploadEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_upload_events(): void
    {
        $this->get(route('admin.upload-events'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_upload_events(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->post(route('admin.upload-events.store'), ['payload' => '{}'])
            ->assertForbidden();
    }

    public function test_superadmin_sees_upload_form(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.upload-events'))
            ->assertOk()
            ->assertSee('Upload Events', false)
            ->assertSee('Submit', false)
            ->assertSee('name="payload"', false);
    }

    public function test_superadmin_can_import_events_and_replace_odds_tree(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
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

        $payload = [
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

        $this->actingAs($admin)
            ->post(route('admin.upload-events.store'), [
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            ])
            ->assertRedirect(route('admin.upload-events'))
            ->assertSessionHas('status');

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
    }

    public function test_invalid_json_shows_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-events.store'), ['payload' => '{not json'])
            ->assertRedirect(route('admin.upload-events'))
            ->assertSessionHasErrors('payload');
    }
}
