<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEditorAccessTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => User::PRIVELEGE_EDITOR,
        ]);
    }

    public function test_editor_can_access_content_admin_routes(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->get(route('admin.site-texts'))
            ->assertOk();

        $this->actingAs($editor)
            ->get(route('admin.seo-pages'))
            ->assertOk();

        $this->actingAs($editor)
            ->get(route('admin.legal-pages'))
            ->assertOk();

        $this->actingAs($editor)
            ->get(route('admin.blogs'))
            ->assertOk();

        $this->actingAs($editor)
            ->get(route('admin.user-texts'))
            ->assertOk();
    }

    public function test_editor_cannot_access_superadmin_routes(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->get(route('admin'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.users'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.upload-events'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.resolve-event'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.resolved-bets'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.prediction-subscriptions'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.tournaments'))
            ->assertForbidden();
    }

    public function test_editor_sees_limited_sidebar_and_admin_link(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->get(route('admin.site-texts'))
            ->assertOk()
            ->assertSee(route('admin.site-texts'), false)
            ->assertSee(route('admin.blogs'), false)
            ->assertSee(route('admin.user-texts'), false)
            ->assertDontSee(route('admin.users'), false)
            ->assertDontSee('Upload Events', false);

        $this->actingAs($editor)
            ->get(url('/'))
            ->assertOk()
            ->assertSee(route('admin.site-texts'), false)
            ->assertSee(__('Admin'), false);
    }

    public function test_user_without_editor_privilege_cannot_access_content_admin_routes(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $this->actingAs($user)
            ->get(route('admin.site-texts'))
            ->assertForbidden();
    }

    public function test_editor_cannot_delete_content_admin_records(): void
    {
        $editor = $this->editor();

        $siteText = \App\Models\SiteText::query()->firstOrFail();
        $legalPage = \App\Models\LegalPage::query()->where('slug', 'cookie-policy')->firstOrFail();
        $blog = \App\Models\BlogPost::query()->create([
            'title' => 'To delete',
            'slug' => 'to-delete',
            'author' => 'Author',
            'body' => '<p>Body</p>',
            'published_at' => now(),
        ]);

        $this->actingAs($editor)
            ->delete(route('admin.site-texts.destroy', $siteText))
            ->assertForbidden();

        $this->actingAs($editor)
            ->delete(route('admin.legal-pages.destroy', $legalPage))
            ->assertForbidden();

        $this->actingAs($editor)
            ->delete(route('admin.blogs.destroy', $blog))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('admin.blogs.edit', $blog))
            ->assertOk()
            ->assertDontSee(__('Delete'), false);
    }
}
