<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserTextsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->with('redeemedPromocode')
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereLike('name', '%'.$search.'%');
            })
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.user-texts.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.user-texts.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->merge($this->nullifyEmptyFields($request->only([
            'name',
            'tagline',
            'city',
            'country',
            'bio',
            'hidden_description',
        ])));

        $validated = $request->validate($this->rules());

        $user->update([
            'name' => $validated['name'] ?? null,
            'tagline' => $validated['tagline'] ?? null,
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'hidden_description' => $validated['hidden_description'] ?? null,
        ]);

        return redirect()
            ->route('admin.user-texts')
            ->with('status', __('User texts updated.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'hidden_description' => ['nullable', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function nullifyEmptyFields(array $fields): array
    {
        foreach ($fields as $key => $value) {
            if (is_string($value) && trim($value) === '') {
                $fields[$key] = null;
            }
        }

        return $fields;
    }
}
