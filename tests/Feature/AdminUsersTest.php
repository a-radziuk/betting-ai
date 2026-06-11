<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Users', false)
            ->assertSee('Listed Member', false)
            ->assertSee('member@example.com', false)
            ->assertSee((string) $member->id, false);
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
