<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\TeamTranslation;
use App\Models\Tournament;
use App\Models\TournamentTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_uses_russian_locale_and_translation_tables(): void
    {
        $this->useLocale('ru');

        [$tournament] = $this->seedLocalizedEvent();

        $this->get('/')
            ->assertOk()
            ->assertSee('Ближайшие 20 предстоящих событий', false)
            ->assertSee('Премьер-лига', false)
            ->assertSee('Арсенал', false)
            ->assertSee('Челси', false)
            ->assertDontSee($tournament->name, false);
    }

    public function test_home_page_falls_back_to_existing_database_values_when_translation_missing(): void
    {
        $this->useLocale('ru');

        [$tournament, $home, $away] = $this->seedLocalizedEvent(false);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ближайшие 20 предстоящих событий', false)
            ->assertSee($tournament->name, false)
            ->assertSee($home->name, false)
            ->assertSee($away->name, false);
    }

    public function test_tournament_page_localizes_standings_team_names_from_team_translation_table(): void
    {
        $this->useLocale('ru');

        [$tournament, $home] = $this->seedTournamentWithStandings();

        TournamentTranslation::query()->create([
            'tournament_id' => $tournament->id,
            'locale' => 'ru',
            'name' => 'Премьер-лига',
        ]);
        TeamTranslation::query()->create([
            'team_id' => $home->id,
            'locale' => 'ru',
            'name' => 'Арсенал',
            'display_name' => 'Арсенал',
        ]);

        $this->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('Премьер-лига', false)
            ->assertSee('Арсенал', false)
            ->assertDontSee('Arsenal', false);
    }

    private function useLocale(string $locale): void
    {
        config([
            'app.locale' => $locale,
            'app.fallback_locale' => 'en',
        ]);
        app()->setLocale($locale);
    }

    /**
     * @return array{0: Tournament, 1: Team, 2: Team}
     */
    private function seedLocalizedEvent(bool $withTranslations = true): array
    {
        $tournament = Tournament::query()->create([
            'name' => 'Premier League',
            'rank' => 1,
        ]);
        $home = Team::query()->create([
            'name' => 'Arsenal',
            'short_name' => 'ARS',
            'league' => 'Premier League',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'Chelsea',
            'short_name' => 'CHE',
            'league' => 'Premier League',
            'tournament_id' => $tournament->id,
        ]);

        if ($withTranslations) {
            TournamentTranslation::query()->create([
                'tournament_id' => $tournament->id,
                'locale' => 'ru',
                'name' => 'Премьер-лига',
            ]);
            TeamTranslation::query()->create([
                'team_id' => $home->id,
                'locale' => 'ru',
                'name' => 'Арсенал',
                'display_name' => 'Арсенал',
            ]);
            TeamTranslation::query()->create([
                'team_id' => $away->id,
                'locale' => 'ru',
                'name' => 'Челси',
                'display_name' => 'Челси',
            ]);
        }

        $event = Event::query()->create([
            'id' => 98001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 98011,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        foreach ([
            [98021, Selection::NAME_HOME, 1.95],
            [98022, Selection::NAME_DRAW, 3.40],
            [98023, Selection::NAME_AWAY, 4.10],
        ] as [$selectionId, $name, $odds]) {
            $selection = Selection::query()->create([
                'id' => $selectionId,
                'market_id' => $market->id,
                'name' => $name,
                'participant_id' => null,
                'handicap' => null,
                'created_at' => now(),
            ]);

            Odd::query()->create([
                'id' => $selectionId + 100,
                'selection_id' => $selection->id,
                'odds' => $odds,
                'probability' => null,
                'is_active' => true,
                'created_at' => now(),
            ]);
        }

        return [$tournament, $home, $away];
    }

    /**
     * @return array{0: Tournament, 1: Team}
     */
    private function seedTournamentWithStandings(): array
    {
        $tournament = Tournament::query()->create([
            'name' => 'Premier League',
            'rank' => 1,
            'standings' => [
                'rows' => [
                    [
                        'position' => 1,
                        'team' => 'Arsenal',
                        'team_display_name' => 'Arsenal',
                        'played' => 10,
                        'won' => 8,
                        'drawn' => 1,
                        'lost' => 1,
                        'goals_for' => 20,
                        'goals_against' => 8,
                        'goal_difference' => 12,
                        'points' => 25,
                        'form' => null,
                    ],
                ],
            ],
        ]);

        $home = Team::query()->create([
            'name' => 'Arsenal',
            'display_name' => 'Arsenal',
            'short_name' => 'ARS',
            'league' => 'Premier League',
            'tournament_id' => $tournament->id,
        ]);

        return [$tournament, $home];
    }
}
