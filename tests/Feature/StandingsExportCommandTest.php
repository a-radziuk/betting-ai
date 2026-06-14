<?php

namespace Tests\Feature;

use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StandingsExportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_standings_and_promrel_to_storage_exports(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'World Cup',
            'rank' => 1,
            'standings' => [
                'groups' => [
                    [
                        'name' => 'Group A',
                        'rows' => [
                            [
                                'position' => 1,
                                'team' => 'Alpha FC',
                                'played' => 1,
                                'won' => 1,
                                'drawn' => 0,
                                'lost' => 0,
                                'goals_for' => 2,
                                'goals_against' => 0,
                                'goal_difference' => 2,
                                'points' => 3,
                                'form' => null,
                            ],
                        ],
                    ],
                ],
            ],
            'standings_promrel' => [
                '1' => [
                    'type' => 'promotion',
                    'name' => 'Playoff',
                    'subtype' => 'champions-league',
                ],
            ],
            'standings_updated_at' => now(),
        ]);

        $path = storage_path('exports/'.$tournament->id.'_standings.json');
        if (is_file($path)) {
            unlink($path);
        }

        $this->assertSame(0, Artisan::call('standings:export', [
            'tournamentId' => $tournament->id,
        ]));

        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($tournament->id, $data['id']);
        $this->assertArrayHasKey('groups', $data['standings']);
        $this->assertSame('Playoff', $data['standings_promrel']['1']['name']);
        $this->assertNotEmpty($data['standings_updated_at']);

        unlink($path);
    }

    public function test_fails_when_tournament_missing(): void
    {
        $this->assertSame(1, Artisan::call('standings:export', [
            'tournamentId' => 999999,
        ]));
    }
}
