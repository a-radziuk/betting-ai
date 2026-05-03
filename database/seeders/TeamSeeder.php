<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    /**
     * Exported from `teams` (stable ids for FK references).
     *
     * @var list<array{id: int, name: string, short_name: string, league: string, tournament_id: int, external_name: string|null}>
     */
    private const ROWS = [
        ['id' => 1, 'name' => 'AFC Bournemouth', 'short_name' => 'AFC', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'AFC Bournemouth'],
        ['id' => 2, 'name' => 'Crystal Palace FC', 'short_name' => 'CRY', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Crystal Palac'],
        ['id' => 3, 'name' => 'Manchester United', 'short_name' => 'MAN', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Manchester United'],
        ['id' => 4, 'name' => 'Liverpool FC', 'short_name' => 'LIV', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Liverpool'],
        ['id' => 5, 'name' => 'Aston Villa', 'short_name' => 'AST', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Aston Villa'],
        ['id' => 6, 'name' => 'Tottenham Hotspur', 'short_name' => 'TOT', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Tottenham Hotspur'],
        ['id' => 7, 'name' => 'Chelsea FC', 'short_name' => 'CHE', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Chelsea'],
        ['id' => 8, 'name' => 'Nottingham Forest', 'short_name' => 'NOT', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Nottingham Forest'],
        ['id' => 9, 'name' => 'Everton FC', 'short_name' => 'EVE', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Everton'],
        ['id' => 10, 'name' => 'Manchester City', 'short_name' => 'MAN', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Manchester City'],
        ['id' => 11, 'name' => 'Sunderland', 'short_name' => 'SUN', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Sunderland'],
        ['id' => 12, 'name' => 'Brighton & Hove Albion', 'short_name' => 'BRI', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Brighton & Hove Albion'],
        ['id' => 13, 'name' => 'Wolverhampton Wanderers', 'short_name' => 'WOL', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Wolverhampton Wanderers'],
        ['id' => 14, 'name' => 'Fulham', 'short_name' => 'FUL', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Fulham'],
        ['id' => 15, 'name' => 'Brentford FC', 'short_name' => 'BRE', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Brentford'],
        ['id' => 16, 'name' => 'Newcastle United', 'short_name' => 'NEW', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Newcastle United'],
        ['id' => 17, 'name' => 'Burnley FC', 'short_name' => 'BUR', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Burnley'],
        ['id' => 18, 'name' => 'West Ham United', 'short_name' => 'WES', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'West Ham United'],
        ['id' => 19, 'name' => 'Arsenal FC', 'short_name' => 'ARS', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Arsenal'],
        ['id' => 20, 'name' => 'Leeds United', 'short_name' => 'LEE', 'league' => 'England', 'tournament_id' => 1, 'external_name' => 'Leeds United'],
    ];

    public function run(): void
    {
        $now = now();

        foreach (self::ROWS as $row) {
            DB::table('teams')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'name' => $row['name'],
                    'short_name' => $row['short_name'],
                    'league' => $row['league'],
                    'tournament_id' => $row['tournament_id'],
                    'external_name' => $row['external_name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
