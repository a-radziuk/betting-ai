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
        gap: 1rem;
        flex-wrap: wrap;
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

    .header-right {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .header-link {
        color: #d6e4ff;
        text-decoration: none;
        font-size: 0.9rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        padding: 0.38rem 0.7rem;
        transition: 0.15s ease;
        background: transparent;
        cursor: pointer;
        font-family: inherit;
    }

    .header-link:hover {
        background: rgba(93, 226, 255, 0.09);
    }

    .subbar {
        border-bottom: 1px solid var(--border);
        background: rgba(6, 11, 22, 0.45);
        padding: 0.65rem 0;
    }

    .subbar-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .subbar-back {
        color: var(--accent);
        text-decoration: none;
        font-size: 0.92rem;
    }

    .hero {
        padding: 2rem 0 1.2rem;
    }

    .hero h1, .hero h2 {
        margin: 0;
        font-size: clamp(1.5rem, 2.8vw, 2.2rem);
        font-weight: 700;
    }

    .hero p {
        margin: 0.55rem 0 0;
        color: var(--muted);
    }

    .hero .meta {
        margin: 0.4rem 0 0;
        color: var(--muted);
        font-size: 0.95rem;
    }

    .card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.3);
    }

    .card-pad {
        padding: 1.25rem 1.5rem;
    }

    .profile-stack {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        padding-bottom: 2rem;
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

    tbody tr[data-clickable] {
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

    .bet-status {
        display: inline-block;
        font-size: 0.74rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        border-radius: 999px;
        padding: 0.3rem 0.55rem;
        border: 1px solid var(--border);
        color: var(--muted);
        background: rgba(12, 21, 36, 0.5);
    }

    .bet-status--pending {
        border-color: rgba(159, 176, 211, 0.45);
        color: #c7d7fa;
        background: rgba(159, 176, 211, 0.1);
    }

    .bet-status--won {
        border-color: rgba(76, 255, 157, 0.35);
        color: var(--ok);
        background: rgba(76, 255, 157, 0.08);
    }

    .bet-status--lost {
        border-color: rgba(255, 120, 120, 0.4);
        color: #ff9a9a;
        background: rgba(255, 120, 120, 0.08);
    }

    .bet-status--void,
    .bet-status--cancelled {
        border-color: rgba(159, 176, 211, 0.35);
        color: var(--muted);
        background: rgba(17, 27, 46, 0.6);
    }

    .dashboard-pagination nav a,
    .dashboard-pagination nav span.inline-flex {
        background: var(--surface) !important;
        color: var(--text) !important;
        border-color: var(--border) !important;
    }

    .dashboard-pagination nav span[aria-disabled] span,
    .dashboard-pagination nav span[aria-current] span {
        background: rgba(93, 226, 255, 0.12) !important;
        color: var(--text) !important;
    }

    .dashboard-pagination .text-gray-700 {
        color: var(--muted) !important;
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

    /* Event odds page */
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

    .market .period {
        color: var(--muted);
        font-size: 0.85rem;
    }

    .market .rows {
        padding: 0.45rem 0.8rem 0.7rem;
    }

    .market .row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.55rem 0.35rem;
        border-bottom: 1px solid rgba(130, 162, 255, 0.1);
        gap: 0.8rem;
    }

    .market .row:last-child {
        border-bottom: 0;
    }

    .market .name {
        color: #dce7ff;
    }

    .market .odds {
        min-width: 72px;
        text-align: right;
        color: #8bffcd;
        font-weight: 700;
    }

    .event-empty {
        border: 1px solid var(--border);
        border-radius: 0.9rem;
        background: var(--surface);
        padding: 1rem;
        color: var(--muted);
    }
</style>
