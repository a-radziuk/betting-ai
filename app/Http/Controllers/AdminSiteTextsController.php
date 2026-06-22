<?php

namespace App\Http\Controllers;

use App\Models\SiteText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSiteTextsController extends Controller
{
    public function index(): View
    {
        $texts = SiteText::query()
            ->orderBy('group')
            ->orderBy('label')
            ->get();

        return view('admin.site-texts.index', [
            'texts' => $texts,
        ]);
    }

    public function create(): View
    {
        return view('admin.site-texts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        SiteText::query()->create($validated);

        return redirect()
            ->route('admin.site-texts')
            ->with('status', __('Site text created.'));
    }

    public function edit(SiteText $siteText): View
    {
        return view('admin.site-texts.edit', [
            'text' => $siteText,
        ]);
    }

    public function update(Request $request, SiteText $siteText): RedirectResponse
    {
        $validated = $request->validate($this->rules($siteText));

        $siteText->update($validated);

        return redirect()
            ->route('admin.site-texts')
            ->with('status', __('Site text updated.'));
    }

    public function destroy(SiteText $siteText): RedirectResponse
    {
        $siteText->delete();

        return redirect()
            ->route('admin.site-texts')
            ->with('status', __('Site text deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?SiteText $text = null): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:191',
                'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/',
                Rule::unique('site_texts', 'key')->ignore($text?->id),
            ],
            'group' => ['nullable', 'string', 'max:64'],
            'label' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string'],
        ];
    }
}
