<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Tests\TestCase;

class TournamentImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_tournament_export_file(): void
    {
        $target = Tournament::query()->create([
            'name' => 'Super League',
            'country' => 'Greece',
            'source' => 'stoiximan',
            'stoiximan_url' => 'https://example.com/super-league',
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

        $exportPath = storage_path('exports/'.$target->id.'_tournament.json');
        if (is_file($exportPath)) {
            unlink($exportPath);
        }

        $this->assertSame(0, Artisan::call('tournament:export', [
            'tournamentId' => $target->id,
        ]));
        $this->assertFileExists($exportPath);

        Tournament::query()->whereKey($target->id)->delete();
        Team::query()->where('country', 'Greece')->delete();

        $exit = Artisan::call('tournament:import', ['file' => $exportPath]);
        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Imported tournament and 2 team(s)', Artisan::output());

        $tournament = Tournament::query()->find($target->id);
        $this->assertNotNull($tournament);
        $this->assertSame('Super League', $tournament->name);
        $this->assertSame('Greece', $tournament->country);
        $this->assertSame('stoiximan', $tournament->source);
        $this->assertSame('https://example.com/super-league', $tournament->stoiximan_url);

        $teams = Team::query()->where('country', 'Greece')->orderBy('name')->pluck('name')->all();
        $this->assertSame(['Olympiacos', 'PAOK'], $teams);

        unlink($exportPath);
    }

    public function test_fails_when_file_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found or not readable');

        Artisan::call('tournament:import', ['file' => '/this/path/does/not/exist-'.uniqid().'.json']);
    }

    public function test_fails_on_invalid_json(): void
    {
        $path = sys_get_temp_dir().'/tournament-import-'.uniqid('', true).'.json';
        file_put_contents($path, '{not json');

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid JSON');

            Artisan::call('tournament:import', ['file' => $path]);
        } finally {
            @unlink($path);
        }
    }

    public function test_fails_when_required_root_keys_are_missing(): void
    {
        $path = sys_get_temp_dir().'/tournament-import-'.uniqid('', true).'.json';
        file_put_contents($path, '{"teams":[]}');

        try {
            $exit = Artisan::call('tournament:import', ['file' => $path]);
            $this->assertSame(1, $exit);
            $this->assertStringContainsString('tournament', Artisan::output());
        } finally {
            @unlink($path);
        }
    }

    public function test_skips_import_when_tournament_already_exists(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Existing League',
            'country' => 'Greece',
        ]);

        $path = sys_get_temp_dir().'/tournament-import-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            'tournament' => [
                'id' => $tournament->id,
                'name' => 'Imported League',
                'country' => 'Greece',
            ],
            'teams' => [
                [
                    'id' => 901,
                    'name' => 'New Team',
                    'short_name' => 'NEW',
                    'league' => 'Imported League',
                    'country' => 'Greece',
                    'tournament_id' => $tournament->id,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('tournament:import', ['file' => $path]);
            $this->assertSame(0, $exit);
            $this->assertStringContainsString("Skipped tournament {$tournament->id} (already exists)", Artisan::output());
            $this->assertNull(Team::query()->find(901));
            $this->assertSame('Existing League', Tournament::query()->find($tournament->id)?->name);
        } finally {
            @unlink($path);
        }
    }

    public function test_skips_existing_teams_with_notice(): void
    {
        $existingTeam = Team::query()->create([
            'name' => 'Existing Team',
            'short_name' => 'EXT',
            'league' => 'League',
            'country' => 'Greece',
        ]);

        $path = sys_get_temp_dir().'/tournament-import-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            'tournament' => [
                'id' => 88,
                'name' => 'New League',
                'country' => 'Greece',
            ],
            'teams' => [
                [
                    'id' => $existingTeam->id,
                    'name' => 'Imported Existing Team',
                    'short_name' => 'IMP',
                    'league' => 'New League',
                    'country' => 'Greece',
                    'tournament_id' => 88,
                ],
                [
                    'id' => 802,
                    'name' => 'Brand New Team',
                    'short_name' => 'BNT',
                    'league' => 'New League',
                    'country' => 'Greece',
                    'tournament_id' => 88,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('tournament:import', ['file' => $path]);
            $output = Artisan::output();

            $this->assertSame(0, $exit);
            $this->assertStringContainsString("Skipped team {$existingTeam->id} (already exists)", $output);
            $this->assertStringContainsString('Imported tournament and 1 team(s)', $output);

            $this->assertNotNull(Tournament::query()->find(88));
            $this->assertSame('Existing Team', Team::query()->find($existingTeam->id)?->name);
            $this->assertSame('Brand New Team', Team::query()->find(802)?->name);
        } finally {
            @unlink($path);
        }
    }
}
