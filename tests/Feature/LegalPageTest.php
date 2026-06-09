<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_legal_page_by_slug(): void
    {
        $page = LegalPage::query()
            ->where('slug', 'privacy-policy')
            ->firstOrFail();

        $this->get(route('legal.show', 'privacy-policy'))
            ->assertOk()
            ->assertSee($page->title, false)
            ->assertSee('Privacy policy content goes here.', false);
    }

    public function test_unknown_legal_page_returns_not_found(): void
    {
        $this->get(route('legal.show', 'missing-page'))
            ->assertNotFound();
    }

    public function test_footer_lists_seeded_legal_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Terms &amp; Conditions', false)
            ->assertSee('Privacy Policy', false)
            ->assertSee('Refund Policy', false);
    }
}
