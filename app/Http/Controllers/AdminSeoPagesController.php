<?php

namespace App\Http\Controllers;

use App\Models\SeoPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSeoPagesController extends Controller
{
    public function index(): View
    {
        $pages = SeoPage::query()
            ->orderBy('label')
            ->get();

        return view('admin.seo-pages.index', [
            'pages' => $pages,
        ]);
    }

    public function edit(SeoPage $seoPage): View
    {
        return view('admin.seo-pages.edit', [
            'page' => $seoPage,
        ]);
    }

    public function update(Request $request, SeoPage $seoPage): RedirectResponse
    {
        $validated = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:320'],
        ]);

        $seoPage->update($validated);

        return redirect()
            ->route('admin.seo-pages')
            ->with('status', __('SEO metadata updated.'));
    }
}
