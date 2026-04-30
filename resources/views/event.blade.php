<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BetAI | Event Odds</title>
    <style>
        :root {
            --bg: #060b16;
            --surface: rgba(17, 27, 46, 0.75);
            --border: rgba(130, 162, 255, 0.2);
            --text: #eaf0ff;
            --muted: #9fb0d3;
            --accent: #5de2ff;
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
        .container { width: min(1100px, calc(100% - 2rem)); margin: 0 auto; }
        .topbar { padding: 1.2rem 0; border-bottom: 1px solid var(--border); }
        .back {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.92rem;
        }
        .hero { padding: 1.2rem 0 1.6rem; }
        .hero h1 { margin: 0; font-size: clamp(1.5rem, 2.8vw, 2.1rem); }
        .meta { margin-top: 0.4rem; color: var(--muted); }
        .market-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            padding-bottom: 2rem;
        }
        .market {
            border: 1px solid var(--border);
            background: var(--surface);
            border-radius: 0.9rem;
            overflow: hidden;
        }
        .market-head {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid var(--border);
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
        }
        .period { color: var(--muted); font-size: 0.85rem; }
        .rows { padding: 0.45rem 0.8rem 0.7rem; }
        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.55rem 0.35rem;
            border-bottom: 1px solid rgba(130, 162, 255, 0.1);
            gap: 0.8rem;
        }
        .row:last-child { border-bottom: 0; }
        .name { color: #dce7ff; }
        .odds {
            min-width: 72px;
            text-align: right;
            color: #8bffcd;
            font-weight: 700;
        }
        .empty {
            border: 1px solid var(--border);
            border-radius: 0.9rem;
            background: var(--surface);
            padding: 1rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="container">
            <a class="back" href="{{ url('/') }}">← Back to events</a>
        </div>
    </div>

    <main class="container">
        <section class="hero">
            <h1>
                {{ $event->homeTeam?->name ?? ('Team #' . $event->home_team_id) }}
                vs
                {{ $event->awayTeam?->name ?? ('Team #' . $event->away_team_id) }}
            </h1>
            <div class="meta">
                Kickoff: {{ $event->start_time?->format('Y-m-d H:i') }} |
                Status: {{ strtoupper($event->status ?? 'unknown') }}
            </div>
        </section>

        @if ($event->markets->isEmpty())
            <div class="empty">No markets available for this event yet.</div>
        @else
            <section class="market-grid">
                @foreach ($event->markets as $market)
                    <article class="market">
                        <div class="market-head">
                            <span>{{ $market->type }}</span>
                            <span class="period">
                                {{ $market->period }}
                                @if (!is_null($market->line))
                                    | Line: {{ $market->line }}
                                @endif
                            </span>
                        </div>
                        <div class="rows">
                            @forelse ($market->selections as $selection)
                                <div class="row">
                                    <span class="name">{{ $selection->name }}</span>
                                    <span class="odds">
                                        {{ number_format(optional($selection->odds->first())->odds ?? 0, 2) }}
                                    </span>
                                </div>
                            @empty
                                <div class="row">
                                    <span class="name">No selections</span>
                                    <span class="odds">-</span>
                                </div>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </main>
</body>
</html>
