<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamTranslation;
use App\Models\Tournament;
use App\Models\TournamentTranslation;
use Database\Seeders\GeorgianTeamTranslationsSeeder;
use Database\Seeders\GeorgianTournamentTranslationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeorgianTranslationsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tournament_seeder_creates_georgian_translation_rows(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Premier League',
            'rank' => 1,
        ]);

        $this->seed(GeorgianTournamentTranslationsSeeder::class);

        $translation = TournamentTranslation::query()
            ->where('tournament_id', $tournament->id)
            ->where('locale', 'ge')
            ->first();

        $this->assertNotNull($translation);
        $this->assertSame('პრემიერ ლიგა', $translation->name);
    }

    public function test_team_seeder_creates_and_updates_georgian_translation_rows(): void
    {
        $team = Team::query()->create([
            'name' => 'Arsenal FC',
            'short_name' => 'ARS',
            'league' => 'Premier League',
        ]);

        TeamTranslation::query()->create([
            'team_id' => $team->id,
            'locale' => 'ge',
            'name' => 'OLD',
            'display_name' => 'OLD',
        ]);

        $this->seed(GeorgianTeamTranslationsSeeder::class);

        $translation = TeamTranslation::query()
            ->where('team_id', $team->id)
            ->where('locale', 'ge')
            ->first();

        $this->assertNotNull($translation);
        $this->assertSame('არსენალი', $translation->name);
        $this->assertSame('არსენალი', $translation->display_name);
        $this->assertSame(1, TeamTranslation::query()->count());
    }
}
