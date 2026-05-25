<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUploadTournamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_tournament(): void
    {
        $this->get(route('admin.upload-tournament'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_upload_tournament(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->post(route('admin.upload-tournament.store'), ['payload' => '{}'])
            ->assertForbidden();
    }

    public function test_superadmin_can_import_tournament_and_teams_from_textarea(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $existingTournament = Tournament::query()->create([
            'id' => 44,
            'name' => 'Old Name',
            'country' => 'Greece',
        ]);
        Team::query()->create([
            'id' => 501,
            'name' => 'Old Team',
            'short_name' => 'OLD',
            'league' => 'Old League',
            'country' => 'Greece',
            'tournament_id' => $existingTournament->id,
        ]);

        $payload = json_encode([
            'tournament' => [
                'id' => 44,
                'name' => 'Super League',
                'country' => 'Greece',
                'rank' => 2,
                'stoiximan_url' => 'https://example.com/super-league',
                'created_at' => '2026-05-01T10:00:00+00:00',
                'updated_at' => '2026-05-02T10:00:00+00:00',
            ],
            'teams' => [
                [
                    'id' => 501,
                    'name' => 'Olympiacos',
                    'display_name' => 'Olympiacos FC',
                    'external_name' => 'Olympiacos',
                    'short_name' => 'OLY',
                    'league' => 'Super League',
                    'country' => 'Greece',
                    'guardian_name' => 'Olympiakos',
                    'tournament_id' => 44,
                    'created_at' => '2026-05-03T10:00:00+00:00',
                    'updated_at' => '2026-05-04T10:00:00+00:00',
                ],
                [
                    'id' => 502,
                    'name' => 'PAOK',
                    'display_name' => null,
                    'external_name' => 'PAOK',
                    'short_name' => 'PAO',
                    'league' => 'Super League',
                    'country' => 'Greece',
                    'guardian_name' => null,
                    'tournament_id' => 999999,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($admin)
            ->post(route('admin.upload-tournament.store'), ['payload' => $payload])
            ->assertRedirect(route('admin.upload-tournament'))
            ->assertSessionHas('status');

        $tournament = Tournament::query()->find(44);
        $this->assertNotNull($tournament);
        $this->assertSame('Super League', $tournament->name);
        $this->assertSame('Greece', $tournament->country);
        $this->assertSame(2, $tournament->rank);

        $team = Team::query()->find(501);
        $this->assertNotNull($team);
        $this->assertSame('Olympiacos', $team->name);
        $this->assertSame('Olympiacos FC', $team->display_name);
        $this->assertSame(44, $team->tournament_id);

        $fallbackTeam = Team::query()->find(502);
        $this->assertNotNull($fallbackTeam);
        $this->assertSame('PAOK', $fallbackTeam->name);
        $this->assertSame(44, $fallbackTeam->tournament_id);
    }

    public function test_invalid_json_shows_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-tournament.store'), ['payload' => '{bad'])
            ->assertRedirect(route('admin.upload-tournament'))
            ->assertSessionHasErrors('payload');
    }

    public function test_missing_required_root_keys_show_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-tournament.store'), ['payload' => '{"teams":[]}' ])
            ->assertRedirect(route('admin.upload-tournament'))
            ->assertSessionHasErrors('payload');
    }
}
