<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\UserPriveleges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUsersController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'privelegeOptions' => UserPriveleges::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        User::query()->create($validated);

        return redirect()
            ->route('admin.users')
            ->with('status', __('User created.'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'privelegeOptions' => UserPriveleges::options(),
            'selectedPriveleges' => UserPriveleges::fromStorage($user->priveleges),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateUser($request, $user);

        if ($validated['password'] === null) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users')
            ->with('status', __('User updated.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $admin = Auth::user();

        if ($admin !== null && $user->id === $admin->id) {
            return redirect()
                ->route('admin.users')
                ->with('status', __('You cannot delete your own account.'));
        }

        $user->delete();

        return redirect()
            ->route('admin.users')
            ->with('status', __('User deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [$user === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'priveleges' => ['nullable', 'array'],
            'priveleges.*' => ['string', Rule::in(UserPriveleges::keys())],
            'see_tips_expires_at' => ['nullable', 'date'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'is_superadmin' => $request->boolean('is_superadmin'),
            'priveleges' => UserPriveleges::toStorage($validated['priveleges'] ?? []),
            'see_tips_expires_at' => $validated['see_tips_expires_at'] ?? null,
            'tagline' => $validated['tagline'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'email_verified_at' => $request->boolean('email_verified') ? now() : null,
        ];
    }
}
