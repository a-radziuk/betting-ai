<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Password;

class Honeypot
{
    public static function timestampToken(?int $timestamp = null): string
    {
        return Crypt::encryptString((string) ($timestamp ?? now()->timestamp));
    }

    public static function isBot(Request $request): bool
    {
        if (! config('honeypot.enabled')) {
            return false;
        }

        $fieldName = (string) config('honeypot.field_name');
        if ($request->filled($fieldName)) {
            return true;
        }

        $timestampField = (string) config('honeypot.timestamp_field');
        $token = $request->input($timestampField);
        if (! is_string($token) || $token === '') {
            return true;
        }

        try {
            $startedAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return true;
        }

        $elapsed = now()->timestamp - $startedAt;

        if ($elapsed < (int) config('honeypot.minimum_submission_seconds')) {
            return true;
        }

        if ($elapsed > (int) config('honeypot.maximum_submission_seconds')) {
            return true;
        }

        return false;
    }

    public static function fakeResponse(Request $request): RedirectResponse
    {
        if ($request->is('forgot-password')) {
            return back()->with('status', __(Password::RESET_LINK_SENT));
        }

        if ($request->is('login')) {
            return redirect()
                ->route('login')
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.failed')]);
        }

        if ($request->is('register')) {
            return redirect()->route('login');
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    public static function payloadForTests(?int $startedAt = null): array
    {
        return [
            (string) config('honeypot.field_name') => '',
            (string) config('honeypot.timestamp_field') => self::timestampToken(
                $startedAt ?? now()->subSeconds((int) config('honeypot.minimum_submission_seconds'))->timestamp
            ),
        ];
    }
}
