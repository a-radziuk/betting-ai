<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_page(): void
    {
        $this->get(route('admin'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_admin_page(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin'))
            ->assertForbidden();
    }

    public function test_superadmin_sees_admin_link_in_header(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->get(url('/'))
            ->assertOk()
            ->assertSee(route('admin'), false)
            ->assertSee(__('Admin'), false);
    }

    public function test_non_superadmin_does_not_see_admin_link_in_header(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
        ]);

        $this->actingAs($user)
            ->get(url('/'))
            ->assertOk()
            ->assertDontSee(route('admin'), false);
    }

    public function test_guest_does_not_see_admin_link_in_header(): void
    {
        $this->get(url('/'))
            ->assertOk()
            ->assertDontSee(route('admin'), false);
    }

    public function test_superadmin_can_access_admin_page(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('hello superadmin', false)
            ->assertSee('Users', false)
            ->assertSee('User Bets', false)
            ->assertSee('Resolved Bets', false)
            ->assertSee('Upload Events', false)
            ->assertSee('Upload Analysis', false)
            ->assertSee('Upload Predictions', false)
            ->assertSee('Resolve Event', false)
            ->assertSee('Legal Pages', false);
    }

    public function test_superadmin_can_access_admin_stub_pages(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.upload-events'))
            ->assertOk()
            ->assertSee('Upload Events', false)
            ->assertSee('Submit', false);

        $this->actingAs($admin)
            ->get(route('admin.upload-analysis'))
            ->assertOk()
            ->assertSee('Upload Analysis', false)
            ->assertSee('Submit', false);

        $this->actingAs($admin)
            ->get(route('admin.upload-predictions'))
            ->assertOk()
            ->assertSee('Upload Predictions', false)
            ->assertSee('Submit', false);

        $this->actingAs($admin)
            ->get(route('admin.resolve-event'))
            ->assertOk()
            ->assertSee('Resolve Event', false)
            ->assertSee('No events ready to resolve', false);
    }
}
