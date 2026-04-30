<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BetAI | Upcoming Football Events</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root {
            --bg: #060b16;
            --bg-soft: #0e1627;
            --surface: rgba(17, 27, 46, 0.75);
            --border: rgba(130, 162, 255, 0.2);
            --text: #eaf0ff;
            --muted: #9fb0d3;
            --accent: #5de2ff;
            --accent2: #8a7bff;
            --ok: #4cff9d;
        }

        * { box-sizing: border-box; }

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

        .container {
            width: min(1100px, calc(100% - 2rem));
            margin: 0 auto;
        }

        .header {
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(10px);
            background: rgba(6, 11, 22, 0.75);
            border-bottom: 1px solid var(--border);
        }

        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: var(--text);
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .logo-badge {
            width: 2rem;
            height: 2rem;
            border-radius: 0.6rem;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #041025;
            box-shadow: 0 8px 25px rgba(93, 226, 255, 0.35);
            font-size: 0.95rem;
        }

        .logo-text {
            font-size: 1.15rem;
            background: linear-gradient(135deg, #ffffff, #8ab7ff 40%, #77f2ff 70%, #9f8dff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .header-tag {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .hero {
            padding: 2rem 0 1.2rem;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(1.5rem, 2.8vw, 2.2rem);
        }

        .hero p {
            margin: 0.55rem 0 0;
            color: var(--muted);
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            text-align: left;
            padding: 0.95rem 1rem;
            font-size: 0.82rem;
            color: #c7d7fa;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
            background: rgba(12, 21, 36, 0.7);
        }

        tbody td {
            padding: 0.95rem 1rem;
            border-bottom: 1px solid rgba(130, 162, 255, 0.1);
            vertical-align: middle;
        }

        tbody tr:hover {
            background: rgba(93, 226, 255, 0.06);
        }

        tbody tr {
            cursor: pointer;
        }

        .status {
            display: inline-block;
            font-size: 0.74rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border: 1px solid rgba(76, 255, 157, 0.35);
            color: var(--ok);
            background: rgba(76, 255, 157, 0.08);
            border-radius: 999px;
            padding: 0.3rem 0.55rem;
        }

        .empty {
            padding: 1.5rem 1rem;
            color: var(--muted);
        }

        footer {
            margin-top: 2rem;
            border-top: 1px solid var(--border);
            color: var(--muted);
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 0 1.6rem;
            font-size: 0.88rem;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="/" class="logo">
                <span class="logo-badge">B</span>
                <span class="logo-text">BetAI</span>
            </a>
            <span class="header-tag">AI-Powered Football Betting Insights</span>
        </div>
    </header>

    <main class="container">
        <section class="hero">
            <h1>Nearest 20 Upcoming Events</h1>
            <p>Real-time lineup of the next football fixtures sorted by kickoff time.</p>
        </section>

        <section class="card">
            @if ($events->isEmpty())
                <div class="empty">No upcoming events found. Seed more data and refresh.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Match</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr onclick="window.location='{{ route('events.show', $event) }}'">
                                <td>{{ $event->start_time->format('Y-m-d H:i') }}</td>
                                <td>
                                    {{ $event->homeTeam?->name ?? ('Team #' . $event->home_team_id) }}
                                    vs
                                    {{ $event->awayTeam?->name ?? ('Team #' . $event->away_team_id) }}
                                </td>
                                <td>
                                    <span class="status">{{ strtoupper($event->status ?? 'unknown') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>

    <footer>
        <div class="container footer-inner">
            <span>BetAI</span>
            <span>Smart football markets, live opportunities, better decisions.</span>
            <span>{{ now()->format('Y') }}</span>
        </div>
    </footer>
</body>
</html>
