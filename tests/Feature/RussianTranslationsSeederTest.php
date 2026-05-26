<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamTranslation;
use App\Models\Tournament;
use App\Models\TournamentTranslation;
use Database\Seeders\RussianTeamTranslationsSeeder;
use Database\Seeders\RussianTournamentTranslationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RussianTranslationsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tournament_seeder_creates_russian_translation_rows(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Premier League',
            'rank' => 1,
        ]);

        $this->seed(RussianTournamentTranslationsSeeder::class);

        $translation = TournamentTranslation::query()
            ->where('tournament_id', $tournament->id)
            ->where('locale', 'ru')
            ->first();

        $this->assertNotNull($translation);
        $this->assertSame('Премьер-лига', $translation->name);
    }

    public function test_team_seeder_creates_and_updates_russian_translation_rows(): void
    {
        $team = Team::query()->create([
            'name' => 'Arsenal FC',
            'short_name' => 'ARS',
            'league' => 'Premier League',
        ]);

        TeamTranslation::query()->create([
            'team_id' => $team->id,
            'locale' => 'ru',
            'name' => 'OLD',
            'display_name' => 'OLD',
        ]);

        $this->seed(RussianTeamTranslationsSeeder::class);

        $translation = TeamTranslation::query()
            ->where('team_id', $team->id)
            ->where('locale', 'ru')
            ->first();

        $this->assertNotNull($translation);
        $this->assertSame('Арсенал', $translation->name);
        $this->assertSame('Арсенал', $translation->display_name);
        $this->assertSame(1, TeamTranslation::query()->count());
    }
}
