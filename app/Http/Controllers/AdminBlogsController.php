<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class AdminBlogsController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.blogs.index', [
            'posts' => $posts,
        ]);
    }

    public function create(): View
    {
        return view('admin.blogs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $this->createPost($validated);

        return redirect()
            ->route('admin.blogs')
            ->with('status', __('Blog post published.'));
    }

    public function createFromJson(): View
    {
        return view('admin.blogs.create-from-json');
    }

    public function storeFromJson(Request $request): RedirectResponse
    {
        $request->validate([
            'json' => ['required', 'string'],
        ]);

        $payload = $this->decodeJsonPayload($request->string('json')->toString());

        $validated = Validator::make($payload, $this->rules())->validate();

        $this->createPost($validated);

        return redirect()
            ->route('admin.blogs')
            ->with('status', __('Blog post published from JSON.'));
    }

    public function edit(BlogPost $blog): View
    {
        return view('admin.blogs.edit', [
            'post' => $blog,
        ]);
    }

    public function update(Request $request, BlogPost $blog): RedirectResponse
    {
        $validated = $request->validate($this->rules($blog));

        if (blank($validated['slug'] ?? null)) {
            $validated['slug'] = BlogPost::uniqueSlug($validated['title'], $blog->id);
        }

        $blog->update($validated);

        return redirect()
            ->route('admin.blogs')
            ->with('status', __('Blog post updated.'));
    }

    public function destroy(BlogPost $blog): RedirectResponse
    {
        $blog->delete();

        return redirect()
            ->route('admin.blogs')
            ->with('status', __('Blog post deleted.'));
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createPost(array $validated): BlogPost
    {
        if (blank($validated['slug'] ?? null)) {
            $validated['slug'] = BlogPost::uniqueSlug($validated['title']);
        }

        return BlogPost::query()->create([
            ...$validated,
            'published_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonPayload(string $json): array
    {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'json' => [__('The JSON is invalid.')],
            ]);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'json' => [__('The JSON must decode to an object.')],
            ]);
        }

        if (isset($payload['text']) && ! isset($payload['body'])) {
            $payload['body'] = $payload['text'];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?BlogPost $post = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blog_posts', 'slug')->ignore($post?->id),
            ],
            'author' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:320'],
        ];
    }
}
