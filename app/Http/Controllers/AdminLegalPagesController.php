<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use App\Support\FaqPageContent;
use App\Support\SubscriptionTermsContent;
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

        if (SubscriptionTermsContent::isManagedPage($legalPage)) {
            $validated['slug'] = SubscriptionTermsContent::slug();
        } elseif (FaqPageContent::isManagedPage($legalPage)) {
            $validated['slug'] = FaqPageContent::slug();
        }

        $legalPage->update($validated);

        return redirect()
            ->route('admin.legal-pages')
            ->with('status', __('Legal page updated.'));
    }

    public function destroy(LegalPage $legalPage): RedirectResponse
    {
        if (SubscriptionTermsContent::isManagedPage($legalPage)) {
            return redirect()
                ->route('admin.legal-pages')
                ->with('status', __('Subscription terms cannot be deleted. Edit the page content instead.'));
        }

        if (FaqPageContent::isManagedPage($legalPage)) {
            return redirect()
                ->route('admin.legal-pages')
                ->with('status', __('FAQ cannot be deleted. Edit the page content instead.'));
        }

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
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:320'],
        ];
    }
}
