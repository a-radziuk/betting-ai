<?php

namespace Tests\Feature;

use App\Models\Team;
use Database\Seeders\SwedenNorwayTeamsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwedenNorwayTeamsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_sweden_and_norway_teams(): void
    {
        $this->seed(SwedenNorwayTeamsSeeder::class);

        $this->assertSame(16, Team::query()->where('country', 'Sweden')->count());
        $this->assertSame(12, Team::query()->where('country', 'Norway')->count());

        $this->assertDatabaseHas('teams', [
            'id' => 149,
            'name' => 'Malmo FF',
            'external_name' => 'Malmö FF',
            'country' => 'Sweden',
            'league' => 'Allsvenskan',
        ]);

        $this->assertDatabaseHas('teams', [
            'id' => 166,
            'name' => 'Bodo-Glimt',
            'external_name' => 'Bodø / Glimt',
            'country' => 'Norway',
            'league' => 'Norway. Eliteserien',
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(SwedenNorwayTeamsSeeder::class);

        Team::query()->whereKey(149)->update(['name' => 'Changed']);

        $this->seed(SwedenNorwayTeamsSeeder::class);

        $this->assertSame('Malmo FF', Team::query()->find(149)?->name);
        $this->assertSame(28, Team::query()->whereIn('country', ['Sweden', 'Norway'])->count());
    }
}
