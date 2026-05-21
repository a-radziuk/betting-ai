<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventAnalysis;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUploadAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_analysis(): void
    {
        $this->get(route('admin.upload-analysis'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_upload_analysis(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->post(route('admin.upload-analysis.store'), ['payload' => '[]'])
            ->assertForbidden();
    }

    public function test_superadmin_can_import_analyses_from_textarea(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $eventId = 1152117365672713705;
        $this->seedEvent($eventId);

        $payload = json_encode([
            [
                'eventId' => (string) $eventId,
                'eventName' => 'Union Berlin vs Augsburg',
                'likely_outcome' => 'AWAY_WIN',
                'approximate_goals' => 2,
                'description' => 'Union Berlin are 12th with little to play for.',
                'home_motivation' => 2,
                'away_motivation' => 6,
                'home_class' => 4,
                'away_class' => 5,
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($admin)
            ->post(route('admin.upload-analysis.store'), ['payload' => $payload])
            ->assertRedirect(route('admin.upload-analysis'))
            ->assertSessionHas('status');

        $analysis = EventAnalysis::query()->first();
        $this->assertNotNull($analysis);
        $this->assertSame($eventId, $analysis->event_id);
        $this->assertSame(EventAnalysis::TYPE_MANUAL, $analysis->type);
        $this->assertSame('Union Berlin vs Augsburg', $analysis->event_name);
    }

    public function test_invalid_json_shows_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-analysis.store'), ['payload' => '{bad'])
            ->assertRedirect(route('admin.upload-analysis'))
            ->assertSessionHasErrors('payload');
    }

    public function test_non_array_root_shows_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-analysis.store'), ['payload' => '{"eventId":"1"}'])
            ->assertRedirect(route('admin.upload-analysis'))
            ->assertSessionHasErrors('payload');
    }

    private function seedEvent(int $eventId, string $status = Event::STATUS_SCHEDULED): void
    {
        $tournament = Tournament::query()->create(['name' => 'Analysis League']);
        $home = Team::query()->create([
            'name' => 'Home',
            'short_name' => 'H',
            'league' => 'L',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'Away',
            'short_name' => 'A',
            'league' => 'L',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::now()->addDay(),
            'status' => $status,
        ]);
    }
}
