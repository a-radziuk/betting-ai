<?php

namespace Tests\Unit;

use App\Models\LegalPage;
use App\Models\User;
use App\Support\PageSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_seo_uses_default_home_metadata(): void
    {
        config(['app.name' => 'BetAI Pro']);

        $seo = PageSeo::forHome();

        $this->assertSame('BetAI Pro | Smart Football Bets with AI', $seo['title']);
        $this->assertStringContainsString('data-driven picks', (string) $seo['description']);
    }

    public function test_legal_page_seo_uses_page_fields_with_fallbacks(): void
    {
        $page = LegalPage::query()->where('slug', 'privacy-policy')->firstOrFail();
        $page->update([
            'meta_title' => 'Privacy at BetAI',
            'meta_description' => 'How we handle your data.',
            'og_title' => 'Privacy Policy',
            'og_description' => 'Read our privacy policy.',
        ]);

        $seo = PageSeo::forLegalPage($page->fresh());

        $this->assertSame('Privacy at BetAI', $seo['title']);
        $this->assertSame('How we handle your data.', $seo['description']);
        $this->assertSame('Privacy Policy', $seo['og_title']);
        $this->assertSame('Read our privacy policy.', $seo['og_description']);
    }

    public function test_player_show_seo_interpolates_name_placeholder(): void
    {
        config(['app.name' => 'BetAI Pro']);

        $user = User::factory()->create([
            'name' => 'Alex Tipster',
        ]);

        $seo = PageSeo::forPlayerShow($user);

        $this->assertSame('Alex Tipster | Player stats | BetAI Pro', $seo['title']);
        $this->assertStringContainsString('Alex Tipster', (string) $seo['description']);
    }
}
