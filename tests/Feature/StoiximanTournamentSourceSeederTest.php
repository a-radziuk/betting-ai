<?php

namespace Tests\Feature;

use App\Models\Tournament;
use Database\Seeders\StoiximanTournamentSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoiximanTournamentSourceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_stoiximan_source_for_hardcoded_tournament_ids(): void
    {
        foreach ([
            1 => 'Premier League',
            2 => 'La Liga',
            3 => 'Serie A',
            4 => 'Ligue 1',
            5 => 'Bundesliga',
            6 => 'World Cup',
        ] as $id => $name) {
            Tournament::query()->create([
                'id' => $id,
                'name' => $name,
                'source' => null,
            ]);
        }

        Tournament::query()->create([
            'id' => 99,
            'name' => 'Allsvenskan',
            'source' => null,
        ]);

        $this->seed(StoiximanTournamentSourceSeeder::class);

        foreach ([1, 2, 3, 4, 5, 6] as $id) {
            $this->assertDatabaseHas('tournaments', [
                'id' => $id,
                'source' => 'stoiximan',
            ]);
        }

        $this->assertNull(Tournament::query()->find(99)?->source);
    }
}
