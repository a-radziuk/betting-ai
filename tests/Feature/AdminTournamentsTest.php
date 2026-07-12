<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTournamentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_tournaments_admin(): void
    {
        $this->get(route('admin.tournaments'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_tournaments_admin(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get(route('admin.tournaments'))
            ->assertForbidden();
    }

    public function test_editor_cannot_access_tournaments_admin(): void
    {
        $editor = User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => User::PRIVELEGE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get(route('admin.tournaments'))
            ->assertForbidden();
    }

    public function test_superadmin_can_create_update_and_delete_tournament(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertSee('name="is_playoff"', false)
            ->assertSee('name="is_active"', false)
            ->assertSee('name="is_fifa"', false)
            ->assertSee('name="source"', false)
            ->assertSee('name="export_marker"', false)
            ->assertSee('name="parimatch_url"', false)
            ->assertSee('name="bbc_standings_url"', false)
            ->assertSee('name="bbc_results_url"', false)
            ->assertSee('name="standings_promrel"', false);

        $this->actingAs($admin)
            ->post(route('admin.tournaments.store'), [
                'name' => 'Champions League',
                'rank' => 2,
                'country' => 'Europe',
                'source' => 'stoiximan',
                'export_marker' => 'cl-2026',
                'is_playoff' => '1',
                'is_active' => '1',
                'is_fifa' => '1',
                'stoiximan_url' => 'https://stoiximan.example/cl',
                'parimatch_url' => 'https://parimatch.example/allsvenskan',
            ])
            ->assertRedirect(route('admin.tournaments'))
            ->assertSessionHas('status');

        $tournament = Tournament::query()->first();
        $this->assertNotNull($tournament);
        $this->assertSame('Champions League', $tournament->name);
        $this->assertSame(2, $tournament->rank);
        $this->assertSame('Europe', $tournament->country);
        $this->assertSame('stoiximan', $tournament->source);
        $this->assertSame('cl-2026', $tournament->export_marker);
        $this->assertTrue($tournament->is_playoff);
        $this->assertTrue($tournament->is_active);
        $this->assertTrue($tournament->is_fifa);
        $this->assertSame('https://stoiximan.example/cl', $tournament->stoiximan_url);
        $this->assertSame('https://parimatch.example/allsvenskan', $tournament->parimatch_url);

        $this->actingAs($admin)
            ->get(route('admin.tournaments'))
            ->assertOk()
            ->assertSee('Champions League', false)
            ->assertSee('Europe', false)
            ->assertSee('stoiximan', false);

        $this->actingAs($admin)
            ->put(route('admin.tournaments.update', $tournament), [
                'name' => 'Europa League',
                'rank' => 3,
                'country' => 'Europe',
                'source' => 'parimatch',
                'export_marker' => '',
                'is_playoff' => '0',
                'is_active' => '0',
                'stoiximan_url' => '',
                'parimatch_url' => 'https://parimatch.example/updated',
                'guardian_standings_url' => 'https://guardian.example/standings',
                'guardian_results_url' => '',
                'bbc_standings_url' => 'https://www.bbc.com/sport/football/swedish-allsvenskan/table',
                'bbc_results_url' => 'https://www.bbc.com/sport/football/swedish-allsvenskan/scores-fixtures',
            ])
            ->assertRedirect(route('admin.tournaments'))
            ->assertSessionHas('status');

        $tournament->refresh();
        $this->assertSame('Europa League', $tournament->name);
        $this->assertSame(3, $tournament->rank);
        $this->assertFalse($tournament->is_playoff);
        $this->assertFalse($tournament->is_active);
        $this->assertFalse($tournament->is_fifa);
        $this->assertSame('parimatch', $tournament->source);
        $this->assertNull($tournament->export_marker);
        $this->assertNull($tournament->stoiximan_url);
        $this->assertSame('https://parimatch.example/updated', $tournament->parimatch_url);
        $this->assertSame('https://guardian.example/standings', $tournament->guardian_standings_url);
        $this->assertSame('https://www.bbc.com/sport/football/swedish-allsvenskan/table', $tournament->bbc_standings_url);
        $this->assertSame('https://www.bbc.com/sport/football/swedish-allsvenskan/scores-fixtures', $tournament->bbc_results_url);

        $this->actingAs($admin)
            ->delete(route('admin.tournaments.destroy', $tournament))
            ->assertRedirect(route('admin.tournaments'))
            ->assertSessionHas('status');

        $this->assertNull(Tournament::query()->find($tournament->id));
    }

    public function test_store_requires_name(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.tournaments.create'))
            ->post(route('admin.tournaments.store'), [
                'name' => '',
            ])
            ->assertRedirect(route('admin.tournaments.create'))
            ->assertSessionHasErrors('name');
    }

    public function test_destroy_nulls_team_tournament_id(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $tournament = Tournament::query()->create(['name' => 'To Delete']);
        $team = Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Club',
            'short_name' => 'CLB',
            'league' => 'L',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.tournaments.destroy', $tournament))
            ->assertRedirect(route('admin.tournaments'));

        $this->assertNull(Tournament::query()->find($tournament->id));
        $this->assertNull($team->fresh()->tournament_id);
    }

    public function test_superadmin_can_update_standings_promrel_from_json(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $tournament = Tournament::query()->create([
            'name' => 'Zone League',
            'standings_promrel' => null,
        ]);

        $promrelJson = json_encode([
            '1' => [
                'type' => 'promotion',
                'name' => 'Champions League',
                'subtype' => 'champions-league',
            ],
            '18' => [
                'type' => 'relegation',
                'name' => 'Championship',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($admin)
            ->put(route('admin.tournaments.update', $tournament), [
                'name' => 'Zone League',
                'rank' => '1',
                'is_active' => '1',
                'standings_promrel' => $promrelJson,
            ])
            ->assertRedirect(route('admin.tournaments'))
            ->assertSessionHas('status');

        $tournament->refresh();
        $this->assertSame('Champions League', $tournament->standings_promrel['1']['name']);
        $this->assertSame('Championship', $tournament->standings_promrel['18']['name']);

        $this->actingAs($admin)
            ->put(route('admin.tournaments.update', $tournament), [
                'name' => 'Zone League',
                'rank' => '1',
                'is_active' => '1',
                'standings_promrel' => '',
            ])
            ->assertRedirect(route('admin.tournaments'));

        $this->assertSame([], $tournament->fresh()->standings_promrel);
    }

    public function test_update_rejects_invalid_standings_promrel_json(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $tournament = Tournament::query()->create(['name' => 'Zone League']);

        $this->actingAs($admin)
            ->from(route('admin.tournaments.edit', $tournament))
            ->put(route('admin.tournaments.update', $tournament), [
                'name' => 'Zone League',
                'rank' => '1',
                'is_active' => '1',
                'standings_promrel' => '{not json',
            ])
            ->assertRedirect(route('admin.tournaments.edit', $tournament))
            ->assertSessionHasErrors('standings_promrel');
    }
}
