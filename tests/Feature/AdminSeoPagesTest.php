<?php

namespace Tests\Feature;

use App\Models\SeoPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_seo_pages(): void
    {
        $this->get(route('admin.seo-pages'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_admin_seo_pages(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.seo-pages'))
            ->assertForbidden();
    }

    public function test_superadmin_can_list_seo_pages(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.seo-pages'))
            ->assertOk()
            ->assertSee('SEO Pages', false)
            ->assertSee('Homepage', false)
            ->assertSee('homepage', false)
            ->assertSee('Forgot password', false);
    }

    public function test_superadmin_can_update_seo_page(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $page = SeoPage::query()
            ->where('key', SeoPage::KEY_LOGIN)
            ->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.seo-pages.update', $page), [
                'meta_title' => 'Custom Login | :app',
                'meta_description' => 'Sign in now.',
                'og_title' => 'Login to :app',
                'og_description' => 'Access your account.',
            ])
            ->assertRedirect(route('admin.seo-pages'));

        $page->refresh();

        $this->assertSame('Custom Login | :app', $page->meta_title);
        $this->assertSame('Sign in now.', $page->meta_description);
        $this->assertSame('Login to :app', $page->og_title);
        $this->assertSame('Access your account.', $page->og_description);
    }

    public function test_login_page_uses_seo_metadata(): void
    {
        $page = SeoPage::query()
            ->where('key', SeoPage::KEY_LOGIN)
            ->firstOrFail();

        $page->update([
            'meta_title' => 'Sign in | :app',
            'meta_description' => 'Login to your betting account.',
        ]);

        config(['app.name' => 'BetAI Pro']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<title>Sign in | BetAI Pro</title>', false)
            ->assertSee('name="description" content="Login to your betting account."', false);
    }
}
