<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FifaRankingsExportCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $exportPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exportPath = storage_path('exports/fifa_rankings.json');
    }

    protected function tearDown(): void
    {
        if (is_file($this->exportPath)) {
            unlink($this->exportPath);
        }

        parent::tearDown();
    }

    public function test_exports_teams_with_fifa_rank_and_points(): void
    {
        $tournament = Tournament::query()->create(['name' => 'World Cup']);

        $france = Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'France',
            'short_name' => 'FRA',
            'league' => 'INT',
            'country' => 'World',
            'fifa_name' => 'France',
            'fifa_rank' => 2,
            'fifa_points' => 1887.11,
        ]);

        $argentina = Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Argentina',
            'short_name' => 'ARG',
            'league' => 'INT',
            'country' => 'World',
            'fifa_name' => 'Argentina',
            'fifa_rank' => 1,
            'fifa_points' => 1889.06,
        ]);

        Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'No Rank',
            'short_name' => 'NR',
            'league' => 'INT',
            'country' => 'World',
            'fifa_name' => 'No Rank',
            'fifa_rank' => null,
            'fifa_points' => null,
        ]);

        Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Rank Only',
            'short_name' => 'RO',
            'league' => 'INT',
            'country' => 'World',
            'fifa_name' => 'Rank Only',
            'fifa_rank' => 10,
            'fifa_points' => null,
        ]);

        $this->assertSame(0, Artisan::call('fifa:rankings-export'));
        $this->assertFileExists($this->exportPath);

        $data = json_decode(file_get_contents($this->exportPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $data);
        $this->assertSame([
            'id' => $argentina->id,
            'fifa_rank' => 1,
            'fifa_points' => 1889.06,
        ], $data[0]);
        $this->assertSame([
            'id' => $france->id,
            'fifa_rank' => 2,
            'fifa_points' => 1887.11,
        ], $data[1]);
        $this->assertSame(['id', 'fifa_rank', 'fifa_points'], array_keys($data[0]));
    }

    public function test_writes_empty_array_when_no_ranked_teams(): void
    {
        $this->assertSame(0, Artisan::call('fifa:rankings-export'));
        $this->assertFileExists($this->exportPath);

        $data = json_decode(file_get_contents($this->exportPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([], $data);
    }
}
