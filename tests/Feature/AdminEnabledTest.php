<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEnabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_return_not_found_when_disabled_for_guest(): void
    {
        config(['app.admin_enabled' => false]);

        $this->get(route('admin'))
            ->assertNotFound();
    }

    public function test_admin_routes_return_not_found_when_disabled_for_superadmin(): void
    {
        config(['app.admin_enabled' => false]);

        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin'))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.legal-pages'))
            ->assertNotFound();
    }

    public function test_admin_routes_are_accessible_for_superadmin_when_enabled(): void
    {
        config(['app.admin_enabled' => true]);

        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin'))
            ->assertOk();
    }
}
