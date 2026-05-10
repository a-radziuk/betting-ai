<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerSubscribePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_page_requires_auth(): void
    {
        $player = User::factory()->create();

        $this->get(route('players.subscribe.show', ['user' => $player->id]))
            ->assertRedirect('/login');
    }

    public function test_shows_subscribe_button_when_not_subscribed(): void
    {
        $viewer = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('players.subscribe.show', ['user' => $player->id]))
            ->assertOk()
            ->assertSee('Subscribe');
    }

    public function test_post_creates_subscription_and_then_page_shows_subscribed_since(): void
    {
        $viewer = User::factory()->create();
        $player = User::factory()->create();

        $this->actingAs($viewer)
            ->post(route('players.subscribe.store', ['user' => $player->id]))
            ->assertRedirect(route('players.subscribe.show', ['user' => $player->id]));

        $this->assertSame(1, UserSubscription::query()->count());

        $this->actingAs($viewer)
            ->get(route('players.subscribe.show', ['user' => $player->id]))
            ->assertOk()
            ->assertSee('Subscribed since')
            ->assertSee('Unsubscribe');
    }
}
