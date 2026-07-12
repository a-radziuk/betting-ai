<?php

namespace Tests\Feature;

use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TournamentSourceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_displays_tournament_source(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Allsvenskan',
            'source' => 'parimatch',
        ]);

        $exit = Artisan::call('tournament:source', ['tournamentId' => $tournament->id]);

        $this->assertSame(0, $exit);
        $this->assertSame('parimatch', trim(Artisan::output()));
    }

    public function test_displays_empty_line_when_source_is_null(): void
    {
        $tournament = Tournament::query()->create(['name' => 'Unknown League']);

        $exit = Artisan::call('tournament:source', ['tournamentId' => $tournament->id]);

        $this->assertSame(0, $exit);
        $this->assertSame('', trim(Artisan::output()));
    }

    public function test_fails_when_tournament_not_found(): void
    {
        $exit = Artisan::call('tournament:source', ['tournamentId' => 99999]);

        $this->assertSame(1, $exit);
    }
}
