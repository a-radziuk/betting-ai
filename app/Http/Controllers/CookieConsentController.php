<?php

namespace App\Http\Controllers;

use App\Models\CookieConsent;
use App\Support\CookieConsentCategories;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CookieConsentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! feature('cookie_consent')) {
            abort(404);
        }

        $optionalKeys = CookieConsentCategories::optionalKeys();

        $validated = $request->validate([
            'consent_uuid' => ['nullable', 'uuid'],
            'action' => [
                'required',
                'string',
                Rule::in([
                    CookieConsent::ACTION_ACCEPTED_ALL,
                    CookieConsent::ACTION_REJECTED_ALL,
                    CookieConsent::ACTION_CUSTOMIZED,
                    CookieConsent::ACTION_WITHDRAWN,
                ]),
            ],
            'categories' => ['required', 'array'],
            'categories.*' => ['boolean'],
        ]);

        $acceptAll = $validated['action'] === CookieConsent::ACTION_ACCEPTED_ALL;
        $categories = CookieConsentCategories::normalize($validated['categories'], $acceptAll);

        foreach (array_keys($validated['categories']) as $key) {
            if (! in_array($key, CookieConsentCategories::keys(), true)) {
                abort(422, 'Unknown cookie category.');
            }
        }

        if (in_array($validated['action'], [
            CookieConsent::ACTION_REJECTED_ALL,
            CookieConsent::ACTION_WITHDRAWN,
        ], true)) {
            foreach ($optionalKeys as $key) {
                $categories[$key] = false;
            }
        }

        $consentUuid = $validated['consent_uuid'] ?? (string) Str::uuid();

        if (($validated['consent_uuid'] ?? null) !== null) {
            CookieConsent::query()
                ->where('consent_uuid', $consentUuid)
                ->whereNull('withdrawn_at')
                ->update(['withdrawn_at' => now()]);
        }

        $record = CookieConsent::query()->create([
            'consent_uuid' => $consentUuid,
            'user_id' => Auth::id(),
            'version' => (string) config('cookie_consent.version', '1'),
            'action' => $validated['action'],
            'categories' => $categories,
            'ip_hash' => $this->hashIp($request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000),
            'withdrawn_at' => in_array($validated['action'], [
                CookieConsent::ACTION_WITHDRAWN,
            ], true) ? now() : null,
        ]);

        return response()->json([
            'consent_uuid' => $record->consent_uuid,
            'version' => $record->version,
            'categories' => $record->categories,
            'action' => $record->action,
        ]);
    }

    private function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash('sha256', $ip.'|'.config('app.key'));
    }
}
