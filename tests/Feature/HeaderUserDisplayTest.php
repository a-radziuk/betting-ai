<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderUserDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_does_not_see_user_name_in_header(): void
    {
        $user = User::factory()->create(['name' => 'Visible Player']);

        $this->get(url('/'))
            ->assertOk()
            ->assertDontSee('Visible Player', false);
    }

    public function test_logged_in_user_sees_name_in_header(): void
    {
        $user = User::factory()->create(['name' => 'Visible Player']);

        $this->actingAs($user)
            ->get(url('/'))
            ->assertOk()
            ->assertSee('Visible Player', false);
    }

    public function test_logged_in_user_without_name_sees_email_in_header(): void
    {
        $user = User::factory()->create([
            'name' => '',
            'email' => 'player@example.com',
        ]);

        $this->actingAs($user)
            ->get(url('/'))
            ->assertOk()
            ->assertSee('player@example.com', false);
    }
}
