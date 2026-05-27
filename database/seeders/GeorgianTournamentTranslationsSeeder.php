<?php

namespace Database\Seeders;

use App\Models\Tournament;
use App\Models\TournamentTranslation;
use Illuminate\Database\Seeder;

class GeorgianTournamentTranslationsSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const TRANSLATIONS = [
        'Bundesliga' => 'ბუნდესლიგა',
        'La Liga' => 'ლა ლიგა',
        'Ligue 1' => 'ლიგა 1',
        'Premier League' => 'პრემიერ ლიგა',
        'Serie A' => 'სერია A',
        'World Cup' => 'მსოფლიო ჩემპიონატი',
    ];

    public function run(): void
    {
        Tournament::query()
            ->get(['id', 'name'])
            ->each(function (Tournament $tournament): void {
                TournamentTranslation::query()->updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'locale' => 'ge',
                    ],
                    [
                        'name' => self::TRANSLATIONS[$tournament->name] ?? $tournament->name,
                    ]
                );
            });
    }
}
