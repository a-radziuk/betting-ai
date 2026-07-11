<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTournamentsController extends Controller
{
    public function index(): View
    {
        $tournaments = Tournament::query()
            ->withCount('teams')
            ->orderBy('rank')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.tournaments.index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function create(): View
    {
        return view('admin.tournaments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedAttributes($request);

        Tournament::query()->create($validated);

        return redirect()
            ->route('admin.tournaments')
            ->with('status', __('Tournament created.'));
    }

    public function edit(Tournament $tournament): View
    {
        return view('admin.tournaments.edit', [
            'tournament' => $tournament,
        ]);
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament->update($this->validatedAttributes($request));

        return redirect()
            ->route('admin.tournaments')
            ->with('status', __('Tournament updated.'));
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        $tournament->delete();

        return redirect()
            ->route('admin.tournaments')
            ->with('status', __('Tournament deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAttributes(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rank' => ['nullable', 'integer', 'min:0'],
            'country' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'is_playoff' => ['boolean'],
            'stoiximan_url' => ['nullable', 'string', 'max:2048'],
            'parimatch_url' => ['nullable', 'string', 'max:2048'],
            'guardian_standings_url' => ['nullable', 'string', 'max:2048'],
            'guardian_results_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $validated['is_playoff'] = $request->boolean('is_playoff');
        $validated['rank'] = filled($validated['rank'] ?? null) ? (int) $validated['rank'] : null;

        foreach (['country', 'source', 'stoiximan_url', 'parimatch_url', 'guardian_standings_url', 'guardian_results_url'] as $key) {
            if (array_key_exists($key, $validated) && is_string($validated[$key]) && trim($validated[$key]) === '') {
                $validated[$key] = null;
            }
        }

        return $validated;
    }
}
