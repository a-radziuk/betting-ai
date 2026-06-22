<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBlogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_blogs(): void
    {
        $this->get(route('admin.blogs'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_admin_blogs(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.blogs'))
            ->assertForbidden();
    }

    public function test_superadmin_can_create_blog_post(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.blogs.store'), [
                'title' => 'First Post',
                'author' => 'Alex Writer',
                'body' => '<p>Hello world</p>',
                'meta_title' => 'First Post SEO',
                'meta_description' => 'A short intro.',
            ])
            ->assertRedirect(route('admin.blogs'));

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'First Post',
            'slug' => 'first-post',
            'author' => 'Alex Writer',
            'meta_title' => 'First Post SEO',
        ]);

        $post = BlogPost::query()->where('slug', 'first-post')->firstOrFail();
        $this->assertNotNull($post->published_at);

        $this->get(route('blog.show', 'first-post'))
            ->assertOk()
            ->assertSee('First Post', false)
            ->assertSee('Alex Writer', false)
            ->assertSee('Hello world', false);
    }

    public function test_superadmin_can_update_and_delete_blog_post(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $post = BlogPost::query()->create([
            'title' => 'Draft Title',
            'slug' => 'draft-title',
            'author' => 'Editor',
            'body' => '<p>Old body</p>',
            'published_at' => now()->subDay(),
        ]);

        $originalPublishedAt = $post->published_at;

        $this->actingAs($admin)
            ->put(route('admin.blogs.update', $post), [
                'title' => 'Updated Title',
                'slug' => 'updated-title',
                'author' => 'Editor',
                'body' => '<p>New body</p>',
            ])
            ->assertRedirect(route('admin.blogs'));

        $post->refresh();

        $this->assertSame('Updated Title', $post->title);
        $this->assertSame('updated-title', $post->slug);
        $this->assertSame($originalPublishedAt->toDateTimeString(), $post->published_at->toDateTimeString());

        $this->actingAs($admin)
            ->delete(route('admin.blogs.destroy', $post))
            ->assertRedirect(route('admin.blogs'));

        $this->assertDatabaseMissing('blog_posts', [
            'id' => $post->id,
        ]);
    }

    public function test_slug_must_be_unique_and_valid(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        BlogPost::query()->create([
            'title' => 'Existing',
            'slug' => 'existing-post',
            'author' => 'Author',
            'body' => '<p>Body</p>',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.blogs.store'), [
                'title' => 'Duplicate',
                'slug' => 'existing-post',
                'author' => 'Author',
                'body' => '<p>Body</p>',
            ])
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)
            ->post(route('admin.blogs.store'), [
                'title' => 'Invalid',
                'slug' => 'Invalid Slug',
                'author' => 'Author',
                'body' => '<p>Body</p>',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_superadmin_can_create_blog_post_from_json(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $json = json_encode([
            'title' => 'JSON Post',
            'author' => 'JSON Author',
            'text' => '<p>From JSON</p>',
            'slug' => 'json-post',
            'meta_title' => 'JSON SEO',
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($admin)
            ->post(route('admin.blogs.store-from-json'), [
                'json' => $json,
            ])
            ->assertRedirect(route('admin.blogs'));

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'JSON Post',
            'slug' => 'json-post',
            'author' => 'JSON Author',
            'body' => '<p>From JSON</p>',
            'meta_title' => 'JSON SEO',
        ]);
    }

    public function test_create_from_json_rejects_invalid_json(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.blogs.store-from-json'), [
                'json' => '{invalid',
            ])
            ->assertSessionHasErrors('json');
    }

    public function test_create_from_json_validates_required_fields(): void
    {
        $admin = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.blogs.store-from-json'), [
                'json' => json_encode(['title' => 'Missing fields'], JSON_THROW_ON_ERROR),
            ])
            ->assertSessionHasErrors(['author', 'body']);
    }
}
