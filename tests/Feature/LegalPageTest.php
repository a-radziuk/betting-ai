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

    public function test_legal_page_content_replaces_env_parameters(): void
    {
        config([
            'legal.date' => '2026-05-27',
            'legal.contact_email' => 'legal@example.com',
            'legal.country' => 'United Kingdom',
            'app.name' => 'BetAI Pro',
            'app.url' => 'https://betai.example',
        ]);

        LegalPage::query()
            ->where('slug', 'privacy-policy')
            ->update([
                'content' => '<p>Contact [WEBSITE NAME] at [CONTACT EMAIL]. Updated [DATE]. Visit [WEBSITE URL]. Governed by [COUNTRY/STATE].</p>',
            ]);

        $this->get(route('legal.show', 'privacy-policy'))
            ->assertOk()
            ->assertSee('Contact BetAI Pro at legal@example.com. Updated 2026-05-27. Visit https://betai.example. Governed by United Kingdom.', false);
    }

    public function test_footer_lists_seeded_legal_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('FAQ', false)
            ->assertSee('Terms &amp; Conditions', false)
            ->assertSee('Privacy Policy', false)
            ->assertSee('Refund Policy', false);
    }
}
