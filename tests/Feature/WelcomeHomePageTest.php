<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeHomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_tournament_and_match_result_odds(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Welcome Test League',
            'rank' => 2,
            'country' => 'Testland',
        ]);

        $home = Team::query()->create([
            'name' => 'Alpha United',
            'short_name' => 'ALP',
            'league' => 'WTL',
            'country' => 'Testland',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'Beta Town',
            'short_name' => 'BET',
            'league' => 'WTL',
            'country' => 'Testland',
            'tournament_id' => $tournament->id,
        ]);

        $event = Event::query()->create([
            'id' => 770001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => now()->addDays(2),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 770010,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selectionId = 770040;
        $oddId = 770050;
        foreach ([
            Selection::NAME_HOME => 1.95,
            Selection::NAME_DRAW => 3.40,
            Selection::NAME_AWAY => 4.25,
        ] as $name => $price) {
            $selection = Selection::query()->create([
                'id' => $selectionId,
                'market_id' => $market->id,
                'name' => $name,
                'participant_id' => null,
                'handicap' => null,
                'created_at' => now(),
            ]);

            Odd::query()->create([
                'id' => $oddId,
                'selection_id' => $selection->id,
                'odds' => $price,
                'probability' => null,
                'is_active' => true,
                'created_at' => now(),
            ]);
            $selectionId++;
            $oddId++;
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Welcome Test League', false)
            ->assertSee('Alpha United', false)
            ->assertSee('Beta Town', false)
            ->assertSee('1.95', false)
            ->assertSee('3.40', false)
            ->assertSee('4.25', false);
    }
}
