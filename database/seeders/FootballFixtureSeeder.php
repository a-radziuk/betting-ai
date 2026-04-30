<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FootballFixtureSeeder extends Seeder
{
    /**
     * Seed football teams + events + betting fixtures.
     */
    public function run(): void
    {
        $this->seedTeams();
        $this->seedEventsMarketsSelectionsAndOdds();
    }

    /**
     * @return void
     */
    private function seedTeams(): void
    {
        $teams = [
            ['Arsenal', 'ARS', 'Premier League'],
            ['Aston Villa', 'AVL', 'Premier League'],
            ['Bournemouth', 'BOU', 'Premier League'],
            ['Brentford', 'BRE', 'Premier League'],
            ['Brighton', 'BHA', 'Premier League'],
            ['Burnley', 'BUR', 'Premier League'],
            ['Chelsea', 'CHE', 'Premier League'],
            ['Crystal Palace', 'CRY', 'Premier League'],
            ['Everton', 'EVE', 'Premier League'],
            ['Fulham', 'FUL', 'Premier League'],
            ['Liverpool', 'LIV', 'Premier League'],
            ['Luton Town', 'LUT', 'Premier League'],
            ['Manchester City', 'MCI', 'Premier League'],
            ['Manchester United', 'MUN', 'Premier League'],
            ['Newcastle United', 'NEW', 'Premier League'],
            ['Nottingham Forest', 'NFO', 'Premier League'],
            ['Sheffield United', 'SHU', 'Premier League'],
            ['Tottenham Hotspur', 'TOT', 'Premier League'],
            ['West Ham United', 'WHU', 'Premier League'],
            ['Wolverhampton Wanderers', 'WOL', 'Premier League'],

            ['Birmingham City', 'BIR', 'Championship'],
            ['Blackburn Rovers', 'BBR', 'Championship'],
            ['Bristol City', 'BRC', 'Championship'],
            ['Cardiff City', 'CAR', 'Championship'],
            ['Coventry City', 'COV', 'Championship'],
            ['Derby County', 'DER', 'Championship'],
            ['Hull City', 'HUL', 'Championship'],
            ['Leeds United', 'LEE', 'Championship'],
            ['Leicester City', 'LEI', 'Championship'],
            ['Middlesbrough', 'MID', 'Championship'],
            ['Millwall', 'MIL', 'Championship'],
            ['Norwich City', 'NOR', 'Championship'],
            ['Oxford United', 'OXF', 'Championship'],
            ['Plymouth Argyle', 'PLY', 'Championship'],
            ['Portsmouth', 'POR', 'Championship'],
            ['Preston North End', 'PNE', 'Championship'],
            ['Queens Park Rangers', 'QPR', 'Championship'],
            ['Sheffield Wednesday', 'SHW', 'Championship'],
            ['Stoke City', 'STK', 'Championship'],
            ['Sunderland', 'SUN', 'Championship'],
        ];

        foreach ($teams as [$name, $shortName, $league]) {
            Team::query()->create([
                'name' => $name,
                'short_name' => $shortName,
                'league' => $league,
            ]);
        }
    }

    /**
     * @return void
     */
    private function seedEventsMarketsSelectionsAndOdds(): void
    {
        $eventId = 1;
        $marketId = 1;
        $selectionId = 1;
        $oddId = 1;

        $leagues = Team::query()
            ->select('league')
            ->distinct()
            ->pluck('league')
            ->all();

        foreach ($leagues as $league) {
            $teamIds = Team::query()
                ->where('league', $league)
                ->pluck('id')
                ->all();

            for ($i = 0; $i < 20; $i++) {
                $homeTeamId = (int) fake()->randomElement($teamIds);
                $awayTeamId = (int) fake()->randomElement($teamIds);

                while ($awayTeamId === $homeTeamId) {
                    $awayTeamId = (int) fake()->randomElement($teamIds);
                }

                $event = Event::query()->create([
                    'id' => $eventId++,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'start_time' => Carbon::now()->addHours(fake()->numberBetween(4, 600)),
                    'status' => Event::STATUS_SCHEDULED,
                ]);

                $marketData = [
                    [Market::TYPE_MATCH_RESULT, null, ['HOME', 'DRAW', 'AWAY']],
                    [Market::TYPE_BTTS, null, ['YES', 'NO']],
                    [Market::TYPE_OVER_UNDER, 2.5, ['OVER', 'UNDER']],
                    [Market::TYPE_HANDICAP, fake()->randomElement([-1.5, -1.0, -0.5, 0.5, 1.0, 1.5]), ['HOME', 'AWAY']],
                ];

                foreach ($marketData as [$type, $line, $selectionNames]) {
                    $market = Market::query()->create([
                        'id' => $marketId++,
                        'event_id' => $event->id,
                        'type' => $type,
                        'period' => Market::PERIOD_FULL_TIME,
                        'line' => $line,
                        'status' => Market::STATUS_OPEN,
                    ]);

                    foreach ($selectionNames as $selectionName) {
                        $selection = Selection::query()->create([
                            'id' => $selectionId++,
                            'market_id' => $market->id,
                            'name' => $selectionName,
                            'participant_id' => null,
                            'handicap' => $type === Market::TYPE_HANDICAP
                                ? ($selectionName === 'HOME' ? $market->line : -1 * (float) $market->line)
                                : null,
                            'created_at' => Carbon::now(),
                        ]);

                        $price = fake()->randomFloat(4, 1.2, 6.5);
                        Odd::query()->create([
                            'id' => $oddId++,
                            'selection_id' => $selection->id,
                            'odds' => $price,
                            'probability' => round(1 / $price, 4),
                            'is_active' => true,
                            'created_at' => Carbon::now(),
                        ]);
                    }
                }
            }
        }
    }
}
