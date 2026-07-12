<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Support\StandingsPromrelDecoder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
            'export_marker' => ['nullable', 'string', 'max:255'],
            'is_playoff' => ['boolean'],
            'is_active' => ['boolean'],
            'is_fifa' => ['boolean'],
            'stoiximan_url' => ['nullable', 'string', 'max:2048'],
            'parimatch_url' => ['nullable', 'string', 'max:2048'],
            'guardian_standings_url' => ['nullable', 'string', 'max:2048'],
            'guardian_results_url' => ['nullable', 'string', 'max:2048'],
            'bbc_standings_url' => ['nullable', 'string', 'max:2048'],
            'bbc_results_url' => ['nullable', 'string', 'max:2048'],
            'standings_promrel' => ['nullable', 'string'],
        ]);

        $validated['is_playoff'] = $request->boolean('is_playoff');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_fifa'] = $request->boolean('is_fifa');
        $validated['rank'] = filled($validated['rank'] ?? null) ? (int) $validated['rank'] : null;

        foreach (['country', 'source', 'export_marker', 'stoiximan_url', 'parimatch_url', 'guardian_standings_url', 'guardian_results_url', 'bbc_standings_url', 'bbc_results_url'] as $key) {
            if (array_key_exists($key, $validated) && is_string($validated[$key]) && trim($validated[$key]) === '') {
                $validated[$key] = null;
            }
        }

        $validated['standings_promrel'] = $this->parseStandingsPromrelInput($validated['standings_promrel'] ?? null);

        return $validated;
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    private function parseStandingsPromrelInput(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode(trim($raw), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'standings_promrel' => __('Standings promotion/relegation zones must be valid JSON.'),
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'standings_promrel' => __('Standings promotion/relegation zones must be a JSON object.'),
            ]);
        }

        $normalized = StandingsPromrelDecoder::decode($decoded);

        return $normalized === [] ? null : $normalized;
    }
}
