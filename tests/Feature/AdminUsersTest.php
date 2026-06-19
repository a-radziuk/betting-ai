<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_users(): void
    {
        $this->get(route('admin.users'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_admin_users(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.users'))
            ->assertForbidden();
    }

    public function test_superadmin_can_list_users(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $member = User::factory()->create([
            'name' => 'Listed Member',
            'email' => 'member@example.com',
            'is_metrics_available' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Users', false)
            ->assertSee('Listed Member', false)
            ->assertSee('member@example.com', false)
            ->assertSee(__('Metrics'), false)
            ->assertSee('data-admin-metrics-toggle', false)
            ->assertSee((string) $member->id, false);
    }

    public function test_guest_cannot_update_user_metrics_availability(): void
    {
        $user = User::factory()->create();

        $this->patchJson(route('admin.users.metrics-availability', $user), [
            'is_metrics_available' => true,
        ])->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_update_user_metrics_availability(): void
    {
        $actor = User::factory()->create([
            'is_superadmin' => false,
        ]);

        $user = User::factory()->create([
            'is_metrics_available' => false,
        ]);

        $this->actingAs($actor)
            ->patchJson(route('admin.users.metrics-availability', $user), [
                'is_metrics_available' => true,
            ])
            ->assertForbidden();

        $this->assertFalse($user->fresh()->is_metrics_available);
    }

    public function test_superadmin_can_update_user_metrics_availability_via_ajax(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $user = User::factory()->create([
            'is_metrics_available' => false,
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.users.metrics-availability', $user), [
                'is_metrics_available' => true,
            ])
            ->assertOk()
            ->assertJson([
                'is_metrics_available' => true,
            ]);

        $this->assertTrue($user->fresh()->is_metrics_available);

        $this->actingAs($admin)
            ->patchJson(route('admin.users.metrics-availability', $user), [
                'is_metrics_available' => false,
            ])
            ->assertOk()
            ->assertJson([
                'is_metrics_available' => false,
            ]);

        $this->assertFalse($user->fresh()->is_metrics_available);
    }

    public function test_superadmin_can_create_user(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Created User',
                'email' => 'created@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_superadmin' => '1',
                'email_verified' => '1',
                'priveleges' => [User::PRIVELEGE_SEE_TIPS],
                'tagline' => 'Tipster',
            ]);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'created@example.com',
            'name' => 'Created User',
        ]);

        $user = User::query()->where('email', 'created@example.com')->firstOrFail();
        $this->assertSame('Created User', $user->name);
        $this->assertTrue($user->is_superadmin);
        $this->assertSame(User::PRIVELEGE_SEE_TIPS, $user->priveleges);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_superadmin_can_set_hidden_description_on_create_and_update(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Noted User',
                'email' => 'noted@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'hidden_description' => 'VIP contact — handle with care.',
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'noted@example.com')->firstOrFail();
        $this->assertSame('VIP contact — handle with care.', $user->hidden_description);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'hidden_description' => 'Updated internal note.',
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Updated internal note.', $user->hidden_description);
    }

    public function test_hidden_description_is_not_shown_on_public_player_page(): void
    {
        $player = User::factory()->create([
            'hidden_description' => 'Secret admin note',
            'tagline' => 'Public tagline',
        ]);

        $html = $this->get(route('players.show', $player))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Public tagline', $html);
        $this->assertStringNotContainsString('Secret admin note', $html);
        $this->assertStringNotContainsString('Hidden description', $html);
    }

    public function test_superadmin_can_create_user_with_avatar(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Avatar User',
                'email' => 'avatar@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'avatar' => $file,
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'avatar@example.com')->firstOrFail();
        $this->assertIsString($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_superadmin_can_update_user_avatar(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $user = User::factory()->create([
            'avatar' => 'avatars/old.jpg',
        ]);
        Storage::disk('public')->put('avatars/old.jpg', 'old');

        $file = UploadedFile::fake()->image('new-avatar.jpg', 120, 120);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'avatar' => $file,
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertIsString($user->avatar);
        $this->assertStringStartsWith('avatars/', $user->avatar);
        $this->assertNotSame('avatars/old.jpg', $user->avatar);
        Storage::disk('public')->assertMissing('avatars/old.jpg');
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_superadmin_can_update_user_without_changing_password(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Before Update',
            'priveleges' => null,
        ]);

        $originalPassword = $user->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'After Update',
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'priveleges' => [User::PRIVELEGE_PLACE_BETS],
            ])
            ->assertRedirect(route('admin.users'));

        $user->refresh();

        $this->assertSame('After Update', $user->name);
        $this->assertSame(User::PRIVELEGE_PLACE_BETS, $user->priveleges);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_superadmin_can_toggle_metrics_available(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $user = User::factory()->create([
            'is_metrics_available' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'is_metrics_available' => '1',
            ])
            ->assertRedirect(route('admin.users'));

        $user->refresh();

        $this->assertTrue($user->is_metrics_available);
        $this->assertTrue($user->isMetricsAvailable());
    }

    public function test_superadmin_can_delete_user(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_superadmin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }
}
