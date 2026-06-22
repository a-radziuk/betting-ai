<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Support\PageSeo;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get(['id', 'title', 'slug', 'author', 'published_at']);

        return view('blog.index', [
            'posts' => $posts,
            'seo' => PageSeo::forBlogIndex(),
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('blog.show', [
            'post' => $post,
            'seo' => PageSeo::forBlogPost($post),
        ]);
    }
}
