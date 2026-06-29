<?php

namespace Tests\Feature;

use App\Models\EventPrediction;
use App\Models\User;
use App\Models\UserPredictionSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserPredictionSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_prediction_subscriptions_admin(): void
    {
        $this->get(route('admin.prediction-subscriptions'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_prediction_subscriptions_admin(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get(route('admin.prediction-subscriptions'))
            ->assertForbidden();
    }

    public function test_editor_cannot_access_prediction_subscriptions_admin(): void
    {
        $editor = User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => User::PRIVELEGE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get(route('admin.prediction-subscriptions'))
            ->assertForbidden();
    }

    public function test_superadmin_can_create_update_and_delete_subscription(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $subscriber = User::factory()->create(['name' => 'Auto Bettor']);

        $this->actingAs($admin)
            ->get(route('admin.prediction-subscriptions.create'))
            ->assertOk()
            ->assertSee('type="number"', false)
            ->assertSee('name="user_id"', false)
            ->assertSee('name="prediction_type"', false);

        $this->actingAs($admin)
            ->post(route('admin.prediction-subscriptions.store'), [
                'user_id' => $subscriber->id,
                'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            ])
            ->assertRedirect(route('admin.prediction-subscriptions'))
            ->assertSessionHas('status');

        $subscription = UserPredictionSubscription::query()->first();
        $this->assertNotNull($subscription);
        $this->assertSame($subscriber->id, $subscription->user_id);

        $this->actingAs($admin)
            ->get(route('admin.prediction-subscriptions'))
            ->assertOk()
            ->assertSee('Auto Bettor', false)
            ->assertSee(EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT, false);

        $this->actingAs($admin)
            ->put(route('admin.prediction-subscriptions.update', $subscription), [
                'user_id' => $subscriber->id,
                'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT,
            ])
            ->assertRedirect(route('admin.prediction-subscriptions'))
            ->assertSessionHas('status');

        $this->assertSame(
            EventPrediction::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT,
            $subscription->fresh()->prediction_type,
        );

        $this->actingAs($admin)
            ->delete(route('admin.prediction-subscriptions.destroy', $subscription))
            ->assertRedirect(route('admin.prediction-subscriptions'))
            ->assertSessionHas('status');

        $this->assertNull(UserPredictionSubscription::query()->find($subscription->id));
    }

    public function test_store_rejects_duplicate_user_and_prediction_type(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $subscriber = User::factory()->create();

        UserPredictionSubscription::query()->create([
            'user_id' => $subscriber->id,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.prediction-subscriptions.create'))
            ->post(route('admin.prediction-subscriptions.store'), [
                'user_id' => $subscriber->id,
                'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            ])
            ->assertRedirect(route('admin.prediction-subscriptions.create'))
            ->assertSessionHasErrors('prediction_type');

        $this->assertSame(1, UserPredictionSubscription::query()->count());
    }

    public function test_store_requires_existing_user_id(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.prediction-subscriptions.create'))
            ->post(route('admin.prediction-subscriptions.store'), [
                'user_id' => 999999,
                'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            ])
            ->assertRedirect(route('admin.prediction-subscriptions.create'))
            ->assertSessionHasErrors('user_id');
    }
}
