<?php

namespace Tests\Feature;

use App\Models\SiteText;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSiteTextsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_site_texts(): void
    {
        $this->get(route('admin.site-texts'))
            ->assertRedirect(route('login'));
    }

    public function test_superadmin_can_list_site_texts(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.site-texts'))
            ->assertOk()
            ->assertSee('Site Texts', false)
            ->assertSee('home.hero.title', false);
    }

    public function test_superadmin_can_create_and_update_site_text(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.site-texts.store'), [
                'key' => 'footer.custom',
                'group' => 'footer',
                'label' => 'Custom footer line',
                'value' => 'Custom copy',
            ])
            ->assertRedirect(route('admin.site-texts'));

        $text = SiteText::query()->where('key', 'footer.custom')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.site-texts.update', $text), [
                'key' => 'footer.custom',
                'group' => 'footer',
                'label' => 'Custom footer line',
                'value' => 'Updated copy',
            ])
            ->assertRedirect(route('admin.site-texts'));

        $this->assertSame('Updated copy', $text->fresh()->value);
    }

    public function test_site_text_helper_reads_managed_copy_on_homepage(): void
    {
        SiteText::query()->where('key', 'home.hero.title')->update([
            'value' => 'Managed hero headline',
        ]);
        app(\App\Services\SiteTextRepository::class)->forget();

        $this->get('/')
            ->assertOk()
            ->assertSee('Managed hero headline', false);
    }
}
