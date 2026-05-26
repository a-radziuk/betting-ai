<?php

namespace Database\Seeders;

use App\Models\Tournament;
use App\Models\TournamentTranslation;
use Illuminate\Database\Seeder;

class RussianTournamentTranslationsSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const TRANSLATIONS = [
        'Bundesliga' => 'Бундеслига',
        'La Liga' => 'Ла Лига',
        'Ligue 1' => 'Лига 1',
        'Premier League' => 'Премьер-лига',
        'Serie A' => 'Серия A',
        'World Cup' => 'Чемпионат мира',
    ];

    public function run(): void
    {
        Tournament::query()
            ->get(['id', 'name'])
            ->each(function (Tournament $tournament): void {
                TournamentTranslation::query()->updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'locale' => 'ru',
                    ],
                    [
                        'name' => self::TRANSLATIONS[$tournament->name] ?? $tournament->name,
                    ]
                );
            });
    }
}
