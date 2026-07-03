<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Tests\TestCase;

class FifaRankingsImportCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $importPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPath = storage_path('app/test_fifa_rankings_import.json');
    }

    protected function tearDown(): void
    {
        if (is_file($this->importPath)) {
            unlink($this->importPath);
        }

        parent::tearDown();
    }

    public function test_updates_teams_from_import_file(): void
    {
        $tournament = Tournament::query()->create(['name' => 'World Cup']);

        $argentina = Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Argentina',
            'short_name' => 'ARG',
            'league' => 'INT',
            'country' => 'World',
            'fifa_rank' => 3,
            'fifa_points' => 1800.00,
        ]);

        $france = Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'France',
            'short_name' => 'FRA',
            'league' => 'INT',
            'country' => 'World',
            'fifa_rank' => 4,
            'fifa_points' => 1790.00,
        ]);

        file_put_contents($this->importPath, json_encode([
            [
                'id' => $argentina->id,
                'fifa_rank' => 1,
                'fifa_points' => 1889.06,
            ],
            [
                'id' => $france->id,
                'fifa_rank' => 2,
                'fifa_points' => 1887.11,
            ],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(0, Artisan::call('fifa:rankings-import', [
            'pathToFile' => $this->importPath,
        ]));

        $this->assertDatabaseHas('teams', [
            'id' => $argentina->id,
            'fifa_rank' => 1,
            'fifa_points' => 1889.06,
        ]);

        $this->assertDatabaseHas('teams', [
            'id' => $france->id,
            'fifa_rank' => 2,
            'fifa_points' => 1887.11,
        ]);
    }

    public function test_skips_unknown_team_ids(): void
    {
        $tournament = Tournament::query()->create(['name' => 'World Cup']);

        $team = Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Spain',
            'short_name' => 'ESP',
            'league' => 'INT',
            'country' => 'World',
            'fifa_rank' => 5,
            'fifa_points' => 1700.00,
        ]);

        file_put_contents($this->importPath, json_encode([
            [
                'id' => $team->id,
                'fifa_rank' => 3,
                'fifa_points' => 1856.03,
            ],
            [
                'id' => 999999,
                'fifa_rank' => 99,
                'fifa_points' => 1000.00,
            ],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(0, Artisan::call('fifa:rankings-import', [
            'pathToFile' => $this->importPath,
        ]));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'fifa_rank' => 3,
            'fifa_points' => 1856.03,
        ]);
    }

    public function test_round_trip_with_export_command(): void
    {
        $tournament = Tournament::query()->create(['name' => 'World Cup']);

        Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Brazil',
            'short_name' => 'BRA',
            'league' => 'INT',
            'country' => 'World',
            'fifa_rank' => 5,
            'fifa_points' => 1840.00,
        ]);

        $exportPath = storage_path('exports/fifa_rankings_roundtrip.json');

        try {
            $this->assertSame(0, Artisan::call('fifa:rankings-export'));

            $exported = json_decode(
                file_get_contents(storage_path('exports/fifa_rankings.json')),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            $exported[0]['fifa_rank'] = 4;
            $exported[0]['fifa_points'] = 1850.50;
            file_put_contents($exportPath, json_encode($exported, JSON_THROW_ON_ERROR));

            $this->assertSame(0, Artisan::call('fifa:rankings-import', [
                'pathToFile' => $exportPath,
            ]));

            $this->assertDatabaseHas('teams', [
                'name' => 'Brazil',
                'fifa_rank' => 4,
                'fifa_points' => 1850.50,
            ]);
        } finally {
            foreach ([$exportPath, storage_path('exports/fifa_rankings.json')] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_fails_when_file_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found or not readable');

        Artisan::call('fifa:rankings-import', [
            'pathToFile' => storage_path('app/missing_fifa_rankings.json'),
        ]);
    }

    public function test_fails_on_invalid_json(): void
    {
        file_put_contents($this->importPath, '{not json');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        Artisan::call('fifa:rankings-import', [
            'pathToFile' => $this->importPath,
        ]);
    }
}
