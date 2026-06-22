<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if (! empty($seo))
        @include('layouts.partials.seo-meta', ['seo' => $seo])
    @else
        <title>{{ $pageTitle ?? app_name() }}</title>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')

    <style>
        .auth-wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }
        .auth-card {
            width: 100%;
            max-width: 30rem;
            margin-top: 1rem;
            padding: 1.25rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            backdrop-filter: blur(10px);
        }
        .auth-subtitle {
            color: var(--muted);
            text-align: center;
            margin-top: 0.35rem;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .auth-card label {
            color: var(--text) !important;
        }

        .auth-card label.inline-flex span {
            color: var(--text) !important;
        }

        .auth-card input[type="text"],
        .auth-card input[type="email"],
        .auth-card input[type="password"] {
            background: var(--bg-soft) !important;
            color: var(--text) !important;
            border-color: var(--border) !important;
        }

        .auth-card input[type="text"]::placeholder,
        .auth-card input[type="email"]::placeholder,
        .auth-card input[type="password"]::placeholder {
            color: var(--muted) !important;
            opacity: 0.82;
        }

        .auth-card input[type="text"]:focus,
        .auth-card input[type="email"]:focus,
        .auth-card input[type="password"]:focus {
            border-color: var(--accent) !important;
            --tw-ring-color: var(--accent) !important;
        }

        .auth-card input[type="checkbox"] {
            background: var(--bg-soft) !important;
            border-color: var(--border) !important;
            color: var(--accent) !important;
            accent-color: var(--accent);
        }

        .auth-card input[type="checkbox"]:focus {
            --tw-ring-color: var(--accent) !important;
        }

        .auth-card .relative.flex.justify-center.text-sm span {
            background: var(--surface) !important;
            color: var(--muted) !important;
        }

        .auth-card a.inline-flex {
            color: var(--text) !important;
            border-color: var(--border) !important;
        }

        .auth-card a.inline-flex:hover {
            background: rgba(58, 167, 255, 0.08) !important;
        }

        .auth-card a.underline {
            color: var(--text) !important;
            text-decoration-color: currentColor;
            text-underline-offset: 0.18em;
        }

        .auth-card a.underline:hover,
        .auth-card a.underline:focus-visible {
            color: var(--accent) !important;
            text-decoration-color: currentColor;
        }

        .auth-wrap .text-\[\\#c7d7fa\\],
        .auth-wrap .text-\[\\#dce7ff\\],
        .auth-wrap .text-\[\\#eaf0ff\\] {
            color: var(--text) !important;
        }

        .auth-wrap .text-\[\\#9fb0d3\\] {
            color: var(--muted) !important;
        }

        .auth-wrap .text-\[\\#8bffcd\\] {
            color: var(--ok) !important;
        }

        .auth-wrap .text-red-300 {
            color: #c45466 !important;
        }

        .auth-wrap .bg-\[\\#13213b\\],
        .auth-wrap .bg-\[\\#0f1b31\\] {
            background: var(--bg-soft) !important;
        }

        .auth-wrap .border-\[\\#3b4e75\\] {
            border-color: var(--border) !important;
        }

        .auth-wrap .placeholder-\[\\#7f93bd\\]::placeholder {
            color: var(--muted) !important;
            opacity: 0.82;
        }

        .auth-wrap .text-\[\\#5de2ff\\] {
            color: var(--accent) !important;
        }

        .auth-wrap .focus\:border-\[\\#5de2ff\\]:focus {
            border-color: var(--accent) !important;
        }

        .auth-wrap .focus\:ring-\[\\#5de2ff\\]:focus {
            --tw-ring-color: var(--accent) !important;
        }

        .auth-wrap .hover\:text-\[\\#eaf0ff\\]:hover {
            color: var(--text) !important;
        }

        .auth-wrap .hover\:bg-\[\\#152540\\]:hover {
            background: rgba(58, 167, 255, 0.08) !important;
        }

        .auth-cookie-settings {
            margin-top: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="auth-wrap">
        <div class="text-center">
            <a href="/">
                <x-application-logo class="w-40 h-auto" />
            </a>
            <p class="auth-subtitle">{{ $subtitle ?? app_brand('Sign in to continue with :app') }}</p>
        </div>

        <div class="auth-card">
            {{ $slot }}
        </div>
    </div>

    @feature('cookie_consent')
        <div class="auth-cookie-settings">
            <button type="button" class="footer-cookie-settings-link" data-cookie-settings-open>
                {{ __('Cookie settings') }}
            </button>
        </div>
    @endfeature

    @include('layouts.partials.cookie-consent')
 </body>
</html>
