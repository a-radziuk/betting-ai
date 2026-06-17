<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FifaRankingsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_matching_teams_and_skips_unknown_fifa_names(): void
    {
        Tournament::query()->create(['name' => 'World Cup', 'rank' => 1]);

        Team::query()->create([
            'tournament_id' => 1,
            'name' => 'Argentina',
            'short_name' => 'ARG',
            'league' => 'INT',
            'country' => 'World',
            'fifa_name' => 'Argentina',
        ]);

        Team::query()->create([
            'tournament_id' => 1,
            'name' => 'France',
            'short_name' => 'FRA',
            'league' => 'INT',
            'country' => 'World',
            'fifa_name' => 'France',
        ]);

        Team::query()->create([
            'tournament_id' => 1,
            'name' => 'Unknown',
            'short_name' => 'UNK',
            'league' => 'INT',
            'country' => 'World',
            'fifa_name' => 'Not On Fifa Page',
        ]);

        Http::fake([
            'https://inside.fifa.com/fifa-world-ranking/men' => Http::response('<html></html>', 200),
            'https://api.fifa.com/api/v3/rankings?gender=1' => Http::response([
                'Results' => [
                    [
                        'TeamName' => [
                            ['Locale' => 'en-GB', 'Description' => 'Argentina'],
                        ],
                        'Rank' => 1,
                        'DecimalTotalPoints' => 1889.06,
                    ],
                    [
                        'TeamName' => [
                            ['Locale' => 'en-GB', 'Description' => 'Spain'],
                        ],
                        'Rank' => 3,
                        'DecimalTotalPoints' => 1856.03,
                    ],
                ],
            ], 200),
        ]);

        $exit = Artisan::call('fifa:rankings');

        $this->assertSame(0, $exit);

        $this->assertDatabaseHas('teams', [
            'name' => 'Argentina',
            'fifa_rank' => 1,
            'fifa_points' => 1889.06,
        ]);

        $this->assertDatabaseHas('teams', [
            'name' => 'France',
            'fifa_rank' => null,
            'fifa_points' => null,
        ]);

        $this->assertDatabaseHas('teams', [
            'name' => 'Unknown',
            'fifa_rank' => null,
            'fifa_points' => null,
        ]);
    }

    public function test_fails_when_fifa_page_is_unreachable(): void
    {
        Http::fake([
            'https://inside.fifa.com/fifa-world-ranking/men' => Http::response('', 500),
        ]);

        $this->assertSame(1, Artisan::call('fifa:rankings'));
    }
}
