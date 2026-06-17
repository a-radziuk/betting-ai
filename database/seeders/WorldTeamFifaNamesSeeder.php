<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class WorldTeamFifaNamesSeeder extends Seeder
{
    /**
     * Maps internal team `name` values to FIFA ranking display names.
     *
     * Sourced from https://inside.fifa.com/fifa-world-ranking/men (June 2026 official ranking)
     * via https://api.fifa.com/api/v3/rankings?gender=1
     *
     * @var array<string, string>
     */
    private const FIFA_NAMES = [
        'Algeria' => 'Algeria',
        'Argentina' => 'Argentina',
        'Australia' => 'Australia',
        'Austria' => 'Austria',
        'Belgium' => 'Belgium',
        'Bosnia & Herzegovina' => 'Bosnia and Herzegovina',
        'Brazil' => 'Brazil',
        'Canada' => 'Canada',
        'Cape Verde' => 'Cabo Verde',
        'Colombia' => 'Colombia',
        'Croatia' => 'Croatia',
        'Curacao' => 'Curaçao',
        'Czechia' => 'Czechia',
        'DR Congo' => 'Congo DR',
        'Ecuador' => 'Ecuador',
        'Egypt' => 'Egypt',
        'England' => 'England',
        'France' => 'France',
        'Germany' => 'Germany',
        'Ghana' => 'Ghana',
        'Haiti' => 'Haiti',
        'Iran' => 'IR Iran',
        'Iraq' => 'Iraq',
        'Ivory Coast' => "Côte d'Ivoire",
        'Japan' => 'Japan',
        'Jordan' => 'Jordan',
        'Mexico' => 'Mexico',
        'Morocco' => 'Morocco',
        'Netherlands' => 'Netherlands',
        'New Zealand' => 'New Zealand',
        'Norway' => 'Norway',
        'Panama' => 'Panama',
        'Paraguay' => 'Paraguay',
        'Portugal' => 'Portugal',
        'Qatar' => 'Qatar',
        'Saudi Arabia' => 'Saudi Arabia',
        'Scotland' => 'Scotland',
        'Senegal' => 'Senegal',
        'South Africa' => 'South Africa',
        'South Korea' => 'Korea Republic',
        'Spain' => 'Spain',
        'Sweden' => 'Sweden',
        'Switzerland' => 'Switzerland',
        'Tunisia' => 'Tunisia',
        'Turkey' => 'Türkiye',
        'Türkiye' => 'Türkiye',
        'Uruguay' => 'Uruguay',
        'USA' => 'USA',
        'Uzbekistan' => 'Uzbekistan',
    ];

    public function run(): void
    {
        Team::query()
            ->where('country', 'World')
            ->get(['id', 'name'])
            ->each(function (Team $team): void {
                $fifaName = self::FIFA_NAMES[$team->name] ?? null;

                if ($fifaName === null) {
                    $this->command?->warn("No FIFA name mapping for World team: {$team->name}");

                    return;
                }

                $team->update(['fifa_name' => $fifaName]);
            });
    }
}
