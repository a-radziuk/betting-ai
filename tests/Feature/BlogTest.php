<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_lists_posts(): void
    {
        BlogPost::query()->create([
            'title' => 'Older Post',
            'slug' => 'older-post',
            'author' => 'Writer A',
            'body' => '<p>Older</p>',
            'published_at' => now()->subDay(),
        ]);

        BlogPost::query()->create([
            'title' => 'Newer Post',
            'slug' => 'newer-post',
            'author' => 'Writer B',
            'body' => '<p>Newer</p>',
            'published_at' => now(),
        ]);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('Blog', false)
            ->assertSee('Newer Post', false)
            ->assertSee('Older Post', false);
    }

    public function test_blog_post_renders_seo_metadata(): void
    {
        BlogPost::query()->create([
            'title' => 'SEO Post',
            'slug' => 'seo-post',
            'author' => 'Writer',
            'body' => '<p>Content</p>',
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'Custom SEO Description',
            'published_at' => now(),
        ]);

        $this->get(route('blog.show', 'seo-post'))
            ->assertOk()
            ->assertSee('<title>Custom SEO Title</title>', false)
            ->assertSee('name="description" content="Custom SEO Description"', false);
    }

    public function test_unknown_blog_post_returns_not_found(): void
    {
        $this->get(route('blog.show', 'missing-post'))
            ->assertNotFound();
    }

    public function test_footer_includes_blog_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('blog.index'), false)
            ->assertSee('Blog', false);
    }
}
