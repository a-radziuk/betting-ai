<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPrediction;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUploadPredictionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_predictions(): void
    {
        $this->get(route('admin.upload-predictions'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_upload_predictions(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->post(route('admin.upload-predictions.store'), ['payload' => '[]'])
            ->assertForbidden();
    }

    public function test_superadmin_can_import_predictions_from_textarea(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        [, $oddId] = $this->seedEventWithOdd(920001);

        $payload = json_encode([
            [
                'type' => 'CUSTOM_TYPE',
                'description' => 'Because form.',
                'odd_id' => $oddId,
                'stake' => 2500,
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($admin)
            ->post(route('admin.upload-predictions.store'), ['payload' => $payload])
            ->assertRedirect(route('admin.upload-predictions'))
            ->assertSessionHas('status');

        $prediction = EventPrediction::query()->first();
        $this->assertNotNull($prediction);
        $this->assertSame('CUSTOM_TYPE', $prediction->prediction_type);
        $this->assertSame('Because form.', $prediction->explanation);
        $this->assertSame($oddId, $prediction->odds_id);
        $this->assertSame(250, $prediction->bank_percentage);
        $this->assertTrue($prediction->is_active);
    }

    public function test_invalid_json_shows_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-predictions.store'), ['payload' => '{bad'])
            ->assertRedirect(route('admin.upload-predictions'))
            ->assertSessionHasErrors('payload');
    }

    public function test_non_array_root_shows_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.upload-predictions.store'), ['payload' => '{"type":"X"}'])
            ->assertRedirect(route('admin.upload-predictions'))
            ->assertSessionHasErrors('payload');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function seedEventWithOdd(int $eventId, string $status = Event::STATUS_SCHEDULED): array
    {
        $tournament = Tournament::query()->create(['name' => 'Import League']);
        $home = Team::query()->create([
            'name' => 'H',
            'short_name' => 'H',
            'league' => 'L',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A',
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

        $market = Market::query()->create([
            'id' => $eventId + 1,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId + 2,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $oddId = $eventId + 3;
        Odd::query()->create([
            'id' => $oddId,
            'selection_id' => $selection->id,
            'odds' => 2.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$eventId, $oddId];
    }
}
