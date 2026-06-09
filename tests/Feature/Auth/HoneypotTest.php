<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Honeypot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class HoneypotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['honeypot.enabled' => true]);
    }

    public function test_login_rejects_filled_honeypot_field(): void
    {
        $user = User::factory()->create();

        $payload = array_merge([
            'email' => $user->email,
            'password' => 'password',
        ], Honeypot::payloadForTests());
        $payload[(string) config('honeypot.field_name')] = 'https://spam.example';

        $this->post('/login', $payload)
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_rejects_too_fast_submission(): void
    {
        $user = User::factory()->create();

        $payload = array_merge([
            'email' => $user->email,
            'password' => 'password',
        ], Honeypot::payloadForTests(now()->timestamp));

        $this->post('/login', $payload)
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_allows_valid_honeypot_payload(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', array_merge([
            'email' => $user->email,
            'password' => 'password',
        ], Honeypot::payloadForTests()))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_register_rejects_filled_honeypot_field(): void
    {
        $payload = array_merge([
            'name' => 'Bot User',
            'email' => 'bot@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], Honeypot::payloadForTests());
        $payload[(string) config('honeypot.field_name')] = 'spam';

        $this->post('/register', $payload)
            ->assertRedirect(route('login'));

        $this->assertGuest();

        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    }

    public function test_forgot_password_fakes_success_for_bots(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $payload = array_merge([
            'email' => $user->email,
        ], Honeypot::payloadForTests());
        $payload[(string) config('honeypot.field_name')] = 'spam';

        $this->post('/forgot-password', $payload)
            ->assertRedirect()
            ->assertSessionHas('status', __(Password::RESET_LINK_SENT));

        Notification::assertNothingSent();
    }

}
