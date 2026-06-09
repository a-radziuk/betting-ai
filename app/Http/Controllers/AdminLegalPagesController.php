<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminLegalPagesController extends Controller
{
    public function index(): View
    {
        $pages = LegalPage::query()
            ->orderBy('title')
            ->get();

        return view('admin.legal-pages.index', [
            'pages' => $pages,
        ]);
    }

    public function create(): View
    {
        return view('admin.legal-pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        LegalPage::query()->create($validated);

        return redirect()
            ->route('admin.legal-pages')
            ->with('status', __('Legal page created.'));
    }

    public function edit(LegalPage $legalPage): View
    {
        return view('admin.legal-pages.edit', [
            'page' => $legalPage,
        ]);
    }

    public function update(Request $request, LegalPage $legalPage): RedirectResponse
    {
        $validated = $request->validate($this->rules($legalPage));

        $legalPage->update($validated);

        return redirect()
            ->route('admin.legal-pages')
            ->with('status', __('Legal page updated.'));
    }

    public function destroy(LegalPage $legalPage): RedirectResponse
    {
        $legalPage->delete();

        return redirect()
            ->route('admin.legal-pages')
            ->with('status', __('Legal page deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?LegalPage $page = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('legal_pages', 'slug')->ignore($page?->id),
            ],
            'content' => ['required', 'string'],
        ];
    }
}
