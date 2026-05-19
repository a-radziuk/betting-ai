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

    public function test_superadmin_can_access_admin_page(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('hello superadmin', false)
            ->assertSee('Upload Events', false)
            ->assertSee('Upload Analysis', false)
            ->assertSee('Upload Predictions', false)
            ->assertSee('Resolve Event', false);
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
            ->assertSee('not implemented yet', false);

        $this->actingAs($admin)
            ->get(route('admin.resolve-event'))
            ->assertOk()
            ->assertSee('Resolve Event', false);
    }
}
