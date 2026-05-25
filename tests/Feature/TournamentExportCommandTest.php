<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TournamentExportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_tournament_and_all_teams_with_same_country_to_storage_exports(): void
    {
        $target = Tournament::query()->create([
            'name' => 'Super League',
            'country' => 'Greece',
        ]);
        Tournament::query()->create([
            'name' => 'La Liga',
            'country' => 'Spain',
        ]);

        Team::query()->create([
            'name' => 'Olympiacos',
            'short_name' => 'OLY',
            'league' => 'Super League',
            'country' => 'Greece',
            'tournament_id' => $target->id,
        ]);
        Team::query()->create([
            'name' => 'PAOK',
            'short_name' => 'PAO',
            'league' => 'Other League',
            'country' => 'Greece',
        ]);
        Team::query()->create([
            'name' => 'Barcelona',
            'short_name' => 'BAR',
            'league' => 'La Liga',
            'country' => 'Spain',
        ]);

        $path = storage_path('exports/'.$target->id.'_tournament.json');
        if (is_file($path)) {
            unlink($path);
        }

        $this->assertSame(0, Artisan::call('tournament:export', [
            'tournamentId' => $target->id,
        ]));

        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($target->id, $data['tournament']['id']);
        $this->assertSame('Greece', $data['tournament']['country']);
        $this->assertCount(2, $data['teams']);
        $this->assertSame(['Olympiacos', 'PAOK'], array_column($data['teams'], 'name'));

        unlink($path);
    }
}
