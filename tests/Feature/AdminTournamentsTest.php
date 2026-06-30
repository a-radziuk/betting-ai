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
            ->assertSee('name="is_playoff"', false);

        $this->actingAs($admin)
            ->post(route('admin.tournaments.store'), [
                'name' => 'Champions League',
                'rank' => 2,
                'country' => 'Europe',
                'is_playoff' => '1',
                'stoiximan_url' => 'https://stoiximan.example/cl',
            ])
            ->assertRedirect(route('admin.tournaments'))
            ->assertSessionHas('status');

        $tournament = Tournament::query()->first();
        $this->assertNotNull($tournament);
        $this->assertSame('Champions League', $tournament->name);
        $this->assertSame(2, $tournament->rank);
        $this->assertSame('Europe', $tournament->country);
        $this->assertTrue($tournament->is_playoff);
        $this->assertSame('https://stoiximan.example/cl', $tournament->stoiximan_url);

        $this->actingAs($admin)
            ->get(route('admin.tournaments'))
            ->assertOk()
            ->assertSee('Champions League', false)
            ->assertSee('Europe', false);

        $this->actingAs($admin)
            ->put(route('admin.tournaments.update', $tournament), [
                'name' => 'Europa League',
                'rank' => 3,
                'country' => 'Europe',
                'is_playoff' => '0',
                'stoiximan_url' => '',
                'guardian_standings_url' => 'https://guardian.example/standings',
                'guardian_results_url' => '',
            ])
            ->assertRedirect(route('admin.tournaments'))
            ->assertSessionHas('status');

        $tournament->refresh();
        $this->assertSame('Europa League', $tournament->name);
        $this->assertSame(3, $tournament->rank);
        $this->assertFalse($tournament->is_playoff);
        $this->assertNull($tournament->stoiximan_url);
        $this->assertSame('https://guardian.example/standings', $tournament->guardian_standings_url);

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
}
