<?php

namespace Database\Seeders;

use App\Models\Tournament;
use Illuminate\Database\Seeder;

class StoiximanTournamentSourceSeeder extends Seeder
{
    /**
     * Tournament IDs present in the database when this seeder was authored.
     *
     * @var list<int>
     */
    private const TOURNAMENT_IDS = [
        1, // Premier League
        2, // La Liga
        3, // Serie A
        4, // Ligue 1
        5, // Bundesliga
        6, // World Cup
    ];

    public function run(): void
    {
        foreach (self::TOURNAMENT_IDS as $tournamentId) {
            Tournament::query()
                ->whereKey($tournamentId)
                ->update(['source' => 'stoiximan']);
        }
    }
}
