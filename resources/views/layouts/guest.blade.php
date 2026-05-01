<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'BetAI') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --bg: #060b16;
            --surface: rgba(17, 27, 46, 0.8);
            --border: rgba(130, 162, 255, 0.22);
            --text: #eaf0ff;
            --muted: #9fb0d3;
        }
        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 15% 10%, rgba(93, 226, 255, 0.14), transparent 35%),
                radial-gradient(circle at 80% 0%, rgba(138, 123, 255, 0.14), transparent 30%),
                linear-gradient(180deg, #050a14 0%, #070f1c 35%, #08101f 100%);
            min-height: 100vh;
        }
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
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.3);
        }
        .auth-subtitle {
            color: var(--muted);
            text-align: center;
            margin-top: 0.35rem;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="auth-wrap">
        <div class="text-center">
            <a href="/">
                <x-application-logo class="w-40 h-auto" />
            </a>
            <p class="auth-subtitle">Sign in to continue with BetAI</p>
        </div>

        <div class="auth-card">
            {{ $slot }}
        </div>
    </div>
 </body>
</html>
