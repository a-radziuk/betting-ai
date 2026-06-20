<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_faq_page(): void
    {
        $page = LegalPage::query()
            ->where('slug', 'faq')
            ->firstOrFail();

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee($page->title, false)
            ->assertSee('Add frequently asked questions and answers here.', false);
    }

    public function test_faq_page_is_linked_in_header_and_footer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('faq'), false)
            ->assertSee('FAQ', false);
    }

    public function test_superadmin_can_update_faq_page(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $page = LegalPage::query()
            ->where('slug', 'faq')
            ->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.legal-pages.update', $page), [
                'title' => 'Help & FAQ',
                'slug' => 'faq',
                'content' => '<p>Updated FAQ content</p>',
            ])
            ->assertRedirect(route('admin.legal-pages'));

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Help &amp; FAQ', false)
            ->assertSee('Updated FAQ content', false);
    }

    public function test_superadmin_cannot_delete_faq_page(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $page = LegalPage::query()
            ->where('slug', 'faq')
            ->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.legal-pages.destroy', $page))
            ->assertRedirect(route('admin.legal-pages'));

        $this->assertDatabaseHas('legal_pages', [
            'id' => $page->id,
            'slug' => 'faq',
        ]);
    }
}
