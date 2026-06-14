<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminUploadStandingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_import_standings(): void
    {
        $this->get(route('admin.upload-standings'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_import_standings(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->post(route('admin.upload-standings.store'), ['payload' => '{}'])
            ->assertForbidden();
    }

    public function test_superadmin_can_import_standings_from_textarea(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $tournament = Tournament::query()->create([
            'name' => 'World Cup',
            'standings' => null,
            'standings_promrel' => null,
        ]);

        $payload = json_encode([
            'id' => $tournament->id,
            'standings' => [
                'groups' => [
                    [
                        'name' => 'Group A',
                        'rows' => [
                            [
                                'position' => 1,
                                'team' => 'Alpha FC',
                                'played' => 1,
                                'won' => 1,
                                'drawn' => 0,
                                'lost' => 0,
                                'goals_for' => 2,
                                'goals_against' => 0,
                                'goal_difference' => 2,
                                'points' => 3,
                                'form' => null,
                            ],
                        ],
                    ],
                ],
            ],
            'standings_promrel' => [
                '1' => [
                    'type' => 'promotion',
                    'name' => 'Playoff',
                    'subtype' => 'champions-league',
                ],
            ],
            'standings_updated_at' => '2026-05-27T12:00:00+00:00',
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($admin)
            ->post(route('admin.upload-standings.store'), ['payload' => $payload])
            ->assertRedirect(route('admin.upload-standings'))
            ->assertSessionHas('status');

        $tournament->refresh();
        $this->assertIsArray($tournament->standings);
        $this->assertArrayHasKey('groups', $tournament->standings);
        $this->assertSame('Playoff', $tournament->standings_promrel['1']['name']);
        $this->assertSame('2026-05-27 12:00:00', $tournament->standings_updated_at?->utc()->format('Y-m-d H:i:s'));
    }

    public function test_superadmin_can_import_standings_from_json_file(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $tournament = Tournament::query()->create([
            'name' => 'Test Cup',
            'standings' => null,
            'standings_promrel' => null,
        ]);

        $json = json_encode([
            'id' => $tournament->id,
            'standings' => [
                'rows' => [
                    [
                        'position' => 1,
                        'team' => 'Leader FC',
                        'played' => 5,
                        'won' => 4,
                        'drawn' => 1,
                        'lost' => 0,
                        'goals_for' => 10,
                        'goals_against' => 2,
                        'goal_difference' => 8,
                        'points' => 13,
                        'form' => null,
                    ],
                ],
            ],
            'standings_promrel' => [
                '1' => [
                    'type' => 'promotion',
                    'name' => 'Champions League',
                    'subtype' => 'champions-league',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $file = UploadedFile::fake()->createWithContent('standings.json', $json);

        $this->actingAs($admin)
            ->post(route('admin.upload-standings.store'), ['file' => $file])
            ->assertRedirect(route('admin.upload-standings'))
            ->assertSessionHas('status');

        $tournament = Tournament::query()->find($tournament->id);
        $this->assertNotNull($tournament);
        $this->assertSame('Leader FC', $tournament->standings['rows'][0]['team']);
        $this->assertSame('Champions League', $tournament->standings_promrel['1']['name']);
    }

    public function test_invalid_json_shows_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-standings.store'), ['payload' => '{bad'])
            ->assertRedirect(route('admin.upload-standings'))
            ->assertSessionHasErrors('payload');
    }

    public function test_missing_tournament_id_shows_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-standings.store'), ['payload' => '{"standings":{}}'])
            ->assertRedirect(route('admin.upload-standings'))
            ->assertSessionHasErrors('payload');
    }

    public function test_unknown_tournament_id_shows_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-standings.store'), ['payload' => '{"id":999999,"standings":{}}'])
            ->assertRedirect(route('admin.upload-standings'))
            ->assertSessionHasErrors('payload');
    }
}
