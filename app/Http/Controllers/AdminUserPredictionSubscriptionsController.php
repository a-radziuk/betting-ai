<?php

namespace App\Http\Controllers;

use App\Models\UserPredictionSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserPredictionSubscriptionsController extends Controller
{
    public function index(): View
    {
        $subscriptions = UserPredictionSubscription::query()
            ->with('user')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.prediction-subscriptions.index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    public function create(): View
    {
        return view('admin.prediction-subscriptions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        UserPredictionSubscription::query()->create($validated);

        return redirect()
            ->route('admin.prediction-subscriptions')
            ->with('status', __('Prediction subscription created.'));
    }

    public function edit(UserPredictionSubscription $subscription): View
    {
        return view('admin.prediction-subscriptions.edit', [
            'subscription' => $subscription,
        ]);
    }

    public function update(Request $request, UserPredictionSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate($this->rules($subscription));

        $subscription->update($validated);

        return redirect()
            ->route('admin.prediction-subscriptions')
            ->with('status', __('Prediction subscription updated.'));
    }

    public function destroy(UserPredictionSubscription $subscription): RedirectResponse
    {
        $subscription->delete();

        return redirect()
            ->route('admin.prediction-subscriptions')
            ->with('status', __('Prediction subscription deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?UserPredictionSubscription $subscription = null): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1', 'exists:users,id'],
            'prediction_type' => [
                'required',
                'string',
                'max:80',
                Rule::unique('user_prediction_subscriptions', 'prediction_type')
                    ->where(fn ($query) => $query->where('user_id', request()->integer('user_id')))
                    ->ignore($subscription?->id),
            ],
        ];
    }
}
