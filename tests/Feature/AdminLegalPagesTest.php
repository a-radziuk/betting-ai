<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_legal_pages(): void
    {
        $this->get(route('admin.legal-pages'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_admin_legal_pages(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.legal-pages'))
            ->assertForbidden();
    }

    public function test_superadmin_can_list_legal_pages(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.legal-pages'))
            ->assertOk()
            ->assertSee('Legal Pages', false)
            ->assertSee('Terms &amp; Conditions', false)
            ->assertSee('privacy-policy', false);
    }

    public function test_superadmin_can_create_legal_page(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.legal-pages.store'), [
                'title' => 'Test Policy',
                'slug' => 'test-policy',
                'content' => '<p>Test content</p>',
            ])
            ->assertRedirect(route('admin.legal-pages'));

        $this->assertDatabaseHas('legal_pages', [
            'title' => 'Test Policy',
            'slug' => 'test-policy',
        ]);

        $this->get(route('legal.show', 'test-policy'))
            ->assertOk()
            ->assertSee('Test content', false);
    }

    public function test_superadmin_can_update_legal_page(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $page = LegalPage::query()
            ->where('slug', 'cookie-policy')
            ->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.legal-pages.update', $page), [
                'title' => 'Updated Cookie Policy',
                'slug' => 'cookie-policy',
                'content' => '<p>Updated cookies</p>',
            ])
            ->assertRedirect(route('admin.legal-pages'));

        $page->refresh();

        $this->assertSame('Updated Cookie Policy', $page->title);
        $this->assertSame('<p>Updated cookies</p>', $page->content);

        $this->get(route('legal.show', 'cookie-policy'))
            ->assertOk()
            ->assertSee('Updated cookies', false);
    }

    public function test_superadmin_can_delete_legal_page(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $page = LegalPage::query()->create([
            'title' => 'Temporary Page',
            'slug' => 'temporary-page',
            'content' => '<p>Delete me</p>',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.legal-pages.destroy', $page))
            ->assertRedirect(route('admin.legal-pages'));

        $this->assertDatabaseMissing('legal_pages', [
            'id' => $page->id,
        ]);
    }

    public function test_superadmin_cannot_delete_subscription_terms_page(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $page = LegalPage::query()
            ->where('slug', 'subscription-terms')
            ->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.legal-pages.destroy', $page))
            ->assertRedirect(route('admin.legal-pages'));

        $this->assertDatabaseHas('legal_pages', [
            'id' => $page->id,
            'slug' => 'subscription-terms',
        ]);
    }

    public function test_slug_must_be_unique_and_valid(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.legal-pages.store'), [
                'title' => 'Duplicate',
                'slug' => 'privacy-policy',
                'content' => '<p>Duplicate</p>',
            ])
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)
            ->post(route('admin.legal-pages.store'), [
                'title' => 'Invalid Slug',
                'slug' => 'Invalid Slug',
                'content' => '<p>Invalid</p>',
            ])
            ->assertSessionHasErrors('slug');
    }
}
