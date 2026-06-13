<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_extended_profile_fields_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'tagline' => 'Sharp on corners',
                'bio' => 'Mostly EPL and La Liga.',
                'city' => 'London',
                'country' => 'United Kingdom',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Sharp on corners', $user->tagline);
        $this->assertSame('Mostly EPL and La Liga.', $user->bio);
        $this->assertSame('London', $user->city);
        $this->assertSame('United Kingdom', $user->country);
    }

    public function test_extended_profile_fields_can_be_cleared(): void
    {
        $user = User::factory()->create([
            'tagline' => 'Old tag',
            'bio' => 'Old bio',
        ]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'tagline' => '',
                'bio' => '',
                'city' => '',
                'country' => '',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNull($user->tagline);
        $this->assertNull($user->bio);
        $this->assertNull($user->city);
        $this->assertNull($user->country);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_photo_field_is_hidden_when_feature_flag_is_disabled(): void
    {
        config(['features.profile_photo' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('Profile photo', false)
            ->assertDontSee('name="avatar"', false);
    }

    public function test_profile_photo_field_is_shown_when_feature_flag_is_enabled(): void
    {
        config(['features.profile_photo' => true]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Profile photo', false)
            ->assertSee('name="avatar"', false);
    }

    public function test_profile_avatar_upload_is_ignored_when_feature_flag_is_disabled(): void
    {
        Storage::fake('public');
        config(['features.profile_photo' => false]);

        $user = User::factory()->create([
            'avatar' => null,
        ]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 100, 100),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_profile_avatar_can_be_uploaded(): void
    {
        Storage::fake('public');
        config(['features.profile_photo' => true]);

        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $file,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertIsString($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_profile_avatar_replacement_removes_previous_upload(): void
    {
        Storage::fake('public');
        config(['features.profile_photo' => true]);

        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('first.jpg'),
        ])->assertSessionHasNoErrors();

        $firstPath = $user->fresh()->avatar;
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('second.jpg'),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
