@php
    $theme = (string) ($theme ?? config('app.theme', 'default'));
    $customThemeView = 'themes.'.$theme.'.styles';
@endphp

{{-- Add custom themes at resources/views/themes/<theme>/styles.blade.php --}}
@if ($theme !== 'default' && view()->exists($customThemeView))
    @include($customThemeView)
@else
<style data-theme="default">
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
        --cookie-consent-bg: #16243f;
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

    .logo--b .logo-badge--outline,
    .app-logo--b .logo-badge--outline {
        background: transparent;
        border: 2px solid var(--accent);
        box-shadow: none;
        color: var(--accent);
    }

    .logo--b .logo-text--solid,
    .app-logo--b .logo-text--solid {
        background: none;
        -webkit-background-clip: unset;
        background-clip: unset;
        color: var(--text);
    }

    .app-logo {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
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

    .welcome-odds {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        color: #eaf0ff;
    }

    .welcome-events-section table.welcome-events-table {
        table-layout: fixed;
        width: 100%;
    }

    .welcome-events-section table.welcome-events-table .welcome-time-col {
        width: 5rem;
        min-width: 5rem;
        max-width: 5rem;
        box-sizing: border-box;
        text-align: center;
        vertical-align: middle;
        font-variant-numeric: tabular-nums;
    }

    .welcome-events-section table.welcome-events-table .welcome-tournament-col {
        width: 11rem;
        min-width: 11rem;
        max-width: 11rem;
        box-sizing: border-box;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }

    .welcome-events-section table.welcome-events-table .welcome-match-col {
        min-width: 0;
    }

    .welcome-events-section table.welcome-events-table .welcome-tips-col {
        width: 4rem;
        min-width: 4rem;
        max-width: 4rem;
        box-sizing: border-box;
        text-align: center;
        vertical-align: middle;
        color: #ffd666;
    }

    .welcome-events-section table.welcome-events-table thead .welcome-tips-col {
        color: #ffd666;
    }

    .welcome-tips-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.9rem;
        height: 1.9rem;
        padding: 0 0.45rem;
        border-radius: 0.5rem;
        font-weight: 800;
        font-size: 0.88rem;
        font-variant-numeric: tabular-nums;
        line-height: 1;
        letter-spacing: 0.02em;
        color: #ffe08a;
        background: linear-gradient(145deg, rgba(255, 214, 102, 0.22), rgba(255, 165, 66, 0.18));
        border: 1px solid rgba(255, 193, 7, 0.55);
        box-shadow:
            0 0 12px rgba(255, 193, 7, 0.2),
            inset 0 1px 0 rgba(255, 255, 255, 0.12);
        cursor: default;
    }

    .welcome-tips-badge--compact {
        min-width: 1.55rem;
        height: 1.55rem;
        padding: 0 0.35rem;
        font-size: 0.76rem;
        border-radius: 0.4rem;
        margin-right: 0.2rem;
        vertical-align: middle;
    }

    .welcome-match-meta-tips {
        display: inline-flex;
        align-items: center;
        gap: 0.15rem;
        font-variant-numeric: tabular-nums;
        color: #ffd666;
    }

    .welcome-events-section table.welcome-events-table .welcome-1x2-col {
        width: 4.75rem;
        min-width: 4.75rem;
        max-width: 4.75rem;
        box-sizing: border-box;
        text-align: center;
        vertical-align: middle;
    }

    .welcome-match-meta {
        display: none;
    }

    @media (max-width: 639px) {
        .welcome-events-section table.welcome-events-table .welcome-time-col,
        .welcome-events-section table.welcome-events-table .welcome-tournament-col {
            display: none;
        }

        .welcome-match-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.15rem 0.35rem;
            margin-top: 0.35rem;
            font-size: 0.78rem;
            line-height: 1.35;
            color: var(--muted);
            font-weight: 400;
        }

        .welcome-match-meta-time {
            font-variant-numeric: tabular-nums;
        }

        .welcome-match-meta-sep {
            opacity: 0.65;
        }

        .welcome-events-section table.welcome-events-table .welcome-tips-col {
            display: none;
        }

        .welcome-events-section table.welcome-events-table .welcome-1x2-col {
            width: 3.55rem;
            min-width: 3.55rem;
            max-width: 3.55rem;
            padding-left: 0.3rem;
            padding-right: 0.3rem;
            font-size: 0.65rem;
            letter-spacing: 0.02em;
        }

        .welcome-events-section table.welcome-events-table .welcome-odds {
            font-size: 0.92rem;
        }
    }

    .welcome-events-section + .welcome-events-section {
        margin-top: 1.75rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(130, 162, 255, 0.15);
    }

    .welcome-events-section-title {
        margin: 0;
        padding: 1rem 1rem 0.65rem;
        font-size: 1.05rem;
        font-weight: 600;
        color: #eaf0ff;
        letter-spacing: 0.02em;
    }

    .welcome-events-section:first-of-type .welcome-events-section-title {
        padding-top: 0.85rem;
    }

    .tournament-page-upcoming {
        margin-bottom: 1.25rem;
    }

    .tournament-page-recent-results {
        margin-bottom: 1.25rem;
    }

    .tournament-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 0.85rem 1rem 0.5rem;
        border-bottom: 1px solid rgba(130, 162, 255, 0.12);
    }

    .tournament-section-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 600;
        color: #eaf0ff;
        letter-spacing: 0.02em;
    }

    .tournament-see-all-link {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--accent);
        text-decoration: none;
        white-space: nowrap;
    }

    .tournament-see-all-link:hover {
        text-decoration: underline;
    }

    .tournament-page-all-results {
        margin-bottom: 1.25rem;
    }

    .welcome-events-section .tournament-results-table {
        table-layout: fixed;
        width: 100%;
    }

    .welcome-events-section .tournament-results-table .welcome-result-score-col {
        width: 6rem;
        min-width: 6rem;
        max-width: 6rem;
        text-align: center;
        box-sizing: border-box;
    }

    .welcome-events-section .tournament-results-table .welcome-match-col {
        min-width: 0;
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

    .footer-legal {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 1rem;
        justify-content: center;
        flex: 1 1 100%;
    }

    .footer-legal a {
        color: var(--muted);
        text-decoration: none;
    }

    .footer-legal a:hover {
        color: var(--text);
        text-decoration: underline;
    }

    /* Event page — player tips */
    .event-tips-section {
        margin-bottom: 1.5rem;
    }

    .event-tips-title {
        margin: 0 0 0.85rem;
        font-size: 1.15rem;
        font-weight: 600;
        color: #eaf0ff;
        letter-spacing: 0.02em;
    }

    .event-tips-grid {
        display: grid;
        gap: 0.85rem;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }

    .event-tip-card {
        border: 1px solid var(--border);
        border-radius: 0.95rem;
        background: var(--surface);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.22);
        padding: 1rem 1.1rem;
    }

    .event-tip-card--won {
        border-color: rgba(34, 197, 94, 0.7);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.22), 0 0 0 1px rgba(34, 197, 94, 0.18);
    }

    .event-tip-card--void {
        border-color: rgba(234, 179, 8, 0.75);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.22), 0 0 0 1px rgba(234, 179, 8, 0.18);
    }

    .event-tip-card--lost {
        border-color: rgba(239, 68, 68, 0.72);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.22), 0 0 0 1px rgba(239, 68, 68, 0.18);
    }

    .event-tip-card-head {
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        margin-bottom: 0.85rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid rgba(130, 162, 255, 0.12);
    }

    .event-tip-card-avatar {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
    }

    .event-tip-card-avatar-img {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 50%;
        object-fit: cover;
        display: block;
        border: 1px solid rgba(93, 226, 255, 0.25);
    }

    .event-tip-card-avatar-placeholder {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1rem;
        color: var(--accent);
        background: rgba(93, 226, 255, 0.1);
        border: 1px solid rgba(93, 226, 255, 0.35);
        line-height: 1;
    }

    .event-tip-card-efficiency {
        min-width: 3.5rem;
        text-align: center;
        font-size: 0.72rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: var(--muted);
        line-height: 1.2;
    }

    .event-tip-card-efficiency--pos {
        color: var(--ok);
    }

    .event-tip-card-efficiency--neg {
        color: #ff9a9a;
    }

    .event-tip-card-user {
        min-width: 0;
        flex: 1;
    }

    .event-tip-card-name-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
    }

    .event-tip-card-name {
        display: block;
        margin: 0;
        flex: 1;
        min-width: 0;
        font-size: 1.02rem;
        font-weight: 600;
        color: var(--text);
        line-height: 1.25;
        text-decoration: none;
        word-break: break-word;
    }

    a.event-tip-card-name:hover {
        color: var(--accent);
    }

    .event-tip-card-result {
        flex-shrink: 0;
        margin: 0;
        font-size: 0.9rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        line-height: 1.3;
        color: var(--muted);
    }

    .event-tip-card-result--pos {
        color: var(--ok);
    }

    .event-tip-card-result--neg {
        color: #ff9a9a;
    }

    .event-tip-card-form {
        margin-top: 0.45rem;
    }

    .event-tip-card-pick-box {
        border: 1px solid rgba(130, 162, 255, 0.2);
        border-radius: 0.75rem;
        background: rgba(6, 11, 22, 0.5);
        padding: 0.85rem 0.95rem;
    }

    .event-tip-card-pick {
        margin: 0;
    }

    .event-tip-card-pick-row {
        margin: 0 0 0.65rem;
    }

    .event-tip-card-pick-row:last-child {
        margin-bottom: 0;
    }

    .event-tip-card-pick-row dt {
        margin: 0 0 0.2rem;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .event-tip-card-pick-row dd {
        margin: 0;
        font-size: 0.95rem;
        color: #dce7ff;
        line-height: 1.35;
    }

    .event-tip-card-pick-row--inline {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.65rem 0.75rem;
    }

    .event-tip-card-pick-row--inline > div {
        min-width: 0;
    }

    @media (max-width: 479px) {
        .event-tip-card-pick-row--inline {
            grid-template-columns: 1fr;
        }
    }

    .event-tip-card-odds {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: #8bffcd;
    }

    .event-tip-card-stake {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: #eaf0ff;
    }

    .event-tip-card-subscribe {
        margin: 0;
        padding-top: 0.15rem;
    }

    .event-tip-card-subscribe-link {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--accent);
        text-decoration: none;
    }

    .event-tip-card-subscribe-link:hover {
        text-decoration: underline;
    }

    /* Event page — league standings */
    .event-page-standings {
        margin-bottom: 1.5rem;
    }

    /* Event page — match analysis */
    .event-analysis-section {
        margin-bottom: 1.5rem;
    }

    .event-analysis-title {
        margin: 0 0 0.85rem;
        font-size: 1.15rem;
        font-weight: 600;
        color: #eaf0ff;
        letter-spacing: 0.02em;
    }

    .event-analysis-card {
        border: 1px solid var(--border);
        border-radius: 0.95rem;
        background: var(--surface);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.22);
        padding: 1.1rem 1.2rem;
    }

    .event-analysis-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem 1.5rem;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(130, 162, 255, 0.12);
    }

    .event-analysis-outcome,
    .event-analysis-goals {
        min-width: 7rem;
    }

    .event-analysis-outcome-label,
    .event-analysis-goals-label {
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .event-analysis-outcome-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--accent);
    }

    .event-analysis-goals-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #eaf0ff;
        font-variant-numeric: tabular-nums;
    }

    .event-analysis-strength {
        margin-left: auto;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #c8d4ff;
        background: rgba(130, 162, 255, 0.12);
        border: 1px solid rgba(130, 162, 255, 0.28);
        font-variant-numeric: tabular-nums;
    }

    .event-analysis-description {
        margin: 0 0 1rem;
        font-size: 0.95rem;
        line-height: 1.55;
        color: var(--text);
    }

    .event-analysis-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr));
        gap: 0.65rem 1rem;
        margin: 0;
    }

    .event-analysis-metric {
        margin: 0;
    }

    .event-analysis-metric-label {
        display: block;
        margin: 0 0 0.2rem;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .event-analysis-metric-value {
        display: block;
        font-size: 1rem;
        font-weight: 700;
        color: #eaf0ff;
        font-variant-numeric: tabular-nums;
    }

    .event-analysis-influenced {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(130, 162, 255, 0.12);
    }

    .event-analysis-influenced-title {
        margin: 0 0 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .event-analysis-influenced-list {
        margin: 0;
        padding-left: 1.15rem;
        font-size: 0.92rem;
        line-height: 1.5;
        color: var(--text);
    }

    .event-analysis-influenced-link {
        color: var(--accent);
        text-decoration: none;
    }

    .event-analysis-influenced-link:hover {
        text-decoration: underline;
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

    .user-results {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        padding: 0;
        background: transparent;
        border: 0;
    }

    @media (max-width: 44rem) {
        .user-results {
            grid-template-columns: 1fr;
        }
    }

    .user-results-item {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 0.95rem 1rem;
        border: 1px solid var(--border);
        border-radius: 0.9rem;
        background: var(--surface);
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25);
    }

    .user-results-label {
        color: var(--muted);
        font-size: 0.85rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .user-results-value {
        color: var(--text);
        font-weight: 900;
        font-size: 1.25rem;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.01em;
    }

    .user-results-in-play-meta {
        color: var(--muted);
        font-size: 0.88rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .user-results-item--chart {
        min-height: 8.5rem;
    }

    .user-results-item--metrics {
        gap: 0.5rem;
    }

    .player-result-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .player-result-outcomes {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.35rem;
        margin: 0;
    }

    .player-result-outcomes .form-icon {
        min-width: 1.75rem;
        margin-right: 0;
        margin-bottom: 0;
    }

    .user-results-metric {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .user-results-metric-label {
        color: var(--muted);
        font-size: 0.82rem;
        letter-spacing: 0.02em;
    }

    .user-results-metric-label-group {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        min-width: 0;
    }

    .user-results-metric--duo {
        flex-direction: column;
        align-items: stretch;
        gap: 0.45rem;
    }

    .user-results-metric-duo-item {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .metric-info {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--muted);
        cursor: help;
        outline: none;
    }

    .metric-info-icon {
        display: block;
    }

    .metric-info:hover,
    .metric-info:focus-visible {
        color: var(--accent);
    }

    .metric-info-tooltip {
        position: absolute;
        bottom: calc(100% + 0.4rem);
        left: 50%;
        z-index: 20;
        transform: translateX(-50%);
        width: max-content;
        max-width: 13rem;
        padding: 0.45rem 0.55rem;
        border-radius: 0.45rem;
        border: 1px solid rgba(93, 226, 255, 0.35);
        background: rgba(12, 21, 36, 0.97);
        color: #dce7ff;
        font-size: 0.72rem;
        font-weight: 500;
        line-height: 1.35;
        letter-spacing: 0;
        text-transform: none;
        text-align: left;
        white-space: normal;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.12s ease;
    }

    .metric-info:hover .metric-info-tooltip,
    .metric-info:focus-visible .metric-info-tooltip {
        opacity: 1;
    }

    .user-results-metric-value {
        color: var(--text);
        font-weight: 700;
        font-size: 0.95rem;
        font-variant-numeric: tabular-nums;
        text-align: right;
    }

    .user-results-chart-latest {
        font-size: 1.05rem;
        margin-bottom: 0.35rem;
    }

    .user-results-chart {
        display: block;
        width: 100%;
        height: 4.5rem;
        margin-top: 0.15rem;
    }

    .user-results-chart--full {
        height: 12rem;
    }

    .user-results-chart-head {
        margin-bottom: 0.35rem;
    }

    .user-results-chart-head .user-results-chart-full-link {
        flex-shrink: 0;
        font-size: 0.82rem;
        text-transform: none;
        letter-spacing: 0;
    }

    .player-result-trend-card .user-results-item--chart {
        display: block;
        width: 100%;
    }

    .user-results-chart-line {
        stroke: #5de2ff;
        stroke-width: 1.75;
        stroke-linecap: round;
        stroke-linejoin: round;
        vector-effect: non-scaling-stroke;
    }

    .user-results-chart-point {
        cursor: pointer;
    }

    .user-results-chart-hit {
        fill: transparent;
        stroke: none;
        pointer-events: all;
    }

    .user-results-chart-dot {
        fill: #5de2ff;
        stroke: #0f1b31;
        stroke-width: 0.6;
        vector-effect: non-scaling-stroke;
        pointer-events: none;
        transition: fill 0.12s ease;
    }

    .user-results-chart-point:hover .user-results-chart-dot,
    .user-results-chart-point:focus-visible .user-results-chart-dot {
        fill: #8bffcd;
        stroke: #eaf0ff;
    }

    .user-results-chart-dot--origin {
        fill: #9fb0d3;
        stroke: #6b7a99;
    }

    .user-results-chart-point--origin:hover .user-results-chart-dot--origin,
    .user-results-chart-point--origin:focus-visible .user-results-chart-dot--origin {
        fill: #b8c8e8;
        stroke: #9fb0d3;
    }

    .user-results-chart-tooltip {
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.12s ease;
    }

    .user-results-chart-point:hover .user-results-chart-tooltip,
    .user-results-chart-point:focus-visible .user-results-chart-tooltip {
        opacity: 1;
    }

    .user-results-chart-tooltip-bg {
        fill: rgba(12, 21, 36, 0.95);
        stroke: rgba(93, 226, 255, 0.45);
        stroke-width: 0.35;
        vector-effect: non-scaling-stroke;
    }

    .user-results-chart-tooltip-text {
        fill: #eaf0ff;
        font-size: 3.25px;
        font-weight: 700;
        font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        font-variant-numeric: tabular-nums;
        pointer-events: none;
    }

    .user-results-chart-zero {
        stroke: rgba(159, 176, 211, 0.45);
        stroke-width: 1;
        stroke-dasharray: 3 3;
        vector-effect: non-scaling-stroke;
    }

    .user-results-chart-caption {
        display: block;
        margin-top: 0.45rem;
        color: var(--muted);
        font-size: 0.78rem;
        letter-spacing: 0.02em;
    }

    .user-results-chart-empty {
        margin: 0.35rem 0 0;
        color: var(--muted);
        font-size: 0.88rem;
        line-height: 1.45;
    }

    .player-stats-download-bar {
        display: flex;
        justify-content: flex-end;
        padding-bottom: 0;
    }

    /* Admin */
    .admin-shell {
        display: grid;
        grid-template-columns: minmax(200px, 240px) minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
        padding-top: 0.5rem;
        padding-bottom: 2rem;
    }

    @media (max-width: 768px) {
        .admin-shell {
            grid-template-columns: 1fr;
        }
    }

    .admin-sidebar {
        padding: 1rem;
        position: sticky;
        top: 1rem;
    }

    .admin-sidebar-title {
        margin: 0 0 0.75rem;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .admin-sidebar-nav {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .admin-sidebar-nav li + li {
        margin-top: 0.25rem;
    }

    .admin-sidebar-link {
        display: block;
        padding: 0.55rem 0.7rem;
        border-radius: 0.55rem;
        font-size: 0.92rem;
        font-weight: 600;
        color: #c8d6f5;
        text-decoration: none;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .admin-sidebar-link:hover {
        color: var(--text);
        background: rgba(93, 226, 255, 0.08);
    }

    .admin-sidebar-link--active {
        color: var(--text);
        background: rgba(93, 226, 255, 0.14);
        border: 1px solid rgba(93, 226, 255, 0.28);
    }

    .admin-main {
        min-width: 0;
    }

    .admin-page-title {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
        color: #eaf0ff;
    }

    .admin-upload-form {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .admin-upload-label {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .admin-upload-textarea {
        width: 100%;
        min-height: 28rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
        font-size: 0.8125rem;
        line-height: 1.45;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: var(--surface);
        color: var(--text);
        resize: vertical;
    }

    .admin-upload-textarea:focus {
        outline: 2px solid var(--accent);
        outline-offset: 1px;
    }

    .admin-upload-submit {
        align-self: flex-start;
        margin-top: 0.25rem;
    }

    .admin-upload-input {
        width: 100%;
        font-size: 0.95rem;
        padding: 0.65rem 0.85rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: var(--surface);
        color: var(--text);
    }

    .admin-upload-input:focus {
        outline: 2px solid var(--accent);
        outline-offset: 1px;
    }

    .admin-upload-hint {
        margin: -0.35rem 0 0;
        font-size: 0.82rem;
        color: var(--muted);
    }

    .admin-upload-hint--error {
        color: #ff8b8b;
    }

    .admin-avatar-field {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.25rem;
    }

    .admin-avatar-field-input {
        flex: 1 1 12rem;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .admin-avatar-preview {
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 999px;
        object-fit: cover;
        border: 2px solid var(--border);
        flex-shrink: 0;
    }

    .admin-avatar-preview--empty {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #1a2233;
        color: var(--muted);
        font-size: 0.75rem;
    }

    .admin-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .admin-form-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-top: 0.25rem;
    }

    .admin-delete-form {
        margin-top: 2rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border);
    }

    .admin-upload-checkbox {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        font-size: 0.92rem;
        color: #dce7ff;
        cursor: pointer;
    }

    .admin-upload-checkbox input {
        margin-top: 0.2rem;
        flex-shrink: 0;
    }

    .admin-upload-fieldset {
        margin: 0;
        padding: 0;
        border: 0;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .admin-upload-textarea--compact {
        min-height: 8rem;
    }

    .admin-flash {
        margin: 0.75rem 0 0;
        padding: 0.65rem 0.85rem;
        border-radius: 0.5rem;
        font-size: 0.95rem;
    }

    .admin-flash--success {
        background: color-mix(in srgb, var(--accent) 12%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent);
    }

    .admin-flash--error {
        background: color-mix(in srgb, #dc2626 10%, transparent);
        border: 1px solid color-mix(in srgb, #dc2626 35%, transparent);
    }

    .admin-empty {
        margin: 1rem 0 0;
        color: var(--muted);
    }

    .admin-table-wrap {
        margin-top: 1rem;
        overflow-x: auto;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .admin-table th,
    .admin-table td {
        padding: 0.55rem 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
    }

    .admin-table th {
        font-weight: 600;
        color: var(--muted);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .admin-table-nowrap {
        white-space: nowrap;
    }

    .admin-table-link {
        color: var(--accent);
        text-decoration: none;
    }

    .admin-table-link:hover {
        text-decoration: underline;
    }

    .admin-table-sub {
        margin-top: 0.2rem;
        font-size: 0.78rem;
        color: var(--muted);
    }

    .admin-table-num {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .admin-table-explanation {
        font-size: 0.85rem;
        color: var(--muted);
        font-style: italic;
        padding-top: 0;
    }

    .admin-pagination {
        margin-top: 1rem;
    }

    .admin-table-actions {
        text-align: right;
        white-space: nowrap;
    }

    .btn-sm {
        padding: 0.35rem 0.75rem;
        font-size: 0.85rem;
    }

    .admin-back-link {
        margin: 0 0 0.75rem;
        font-size: 0.9rem;
    }

    .admin-back-link a {
        color: var(--accent);
        text-decoration: none;
    }

    .admin-back-link a:hover {
        text-decoration: underline;
    }

    .admin-resolve-form {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1.25rem;
        max-width: 20rem;
    }

    .admin-resolve-score-input {
        width: 100%;
        padding: 0.55rem 0.75rem;
        font-size: 1.1rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: var(--surface);
        color: var(--text);
    }

    .admin-resolve-score-input:focus {
        outline: 2px solid var(--accent);
        outline-offset: 1px;
    }

    .admin-section-divider {
        margin: 2rem 0 1.25rem;
        border: 0;
        border-top: 1px solid var(--border);
    }

    .admin-section-title {
        margin: 0 0 0.35rem;
        font-size: 1.15rem;
        font-weight: 600;
    }

    .admin-abandon-form {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 0.75rem;
        max-width: 32rem;
    }

    .admin-abandon-textarea {
        width: 100%;
        min-height: 6rem;
        padding: 0.55rem 0.75rem;
        font-size: 0.95rem;
        line-height: 1.45;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: var(--surface);
        color: var(--text);
        resize: vertical;
    }

    .admin-abandon-textarea:focus {
        outline: 2px solid #dc2626;
        outline-offset: 1px;
    }

    .btn-danger {
        background: #dc2626;
        border-color: #dc2626;
        color: #fff;
    }

    .btn-danger:hover {
        background: #b91c1c;
        border-color: #b91c1c;
        color: #fff;
    }

    .admin-abandon-submit {
        align-self: flex-start;
        margin-top: 0.25rem;
    }

    .admin-page-meta {
        margin: 0.65rem 0 0;
        font-size: 0.92rem;
        color: #9fb0d3;
    }

    .player-current-bet-explanation-row td {
        border-top: none;
        padding-top: 0;
    }

    .player-current-bet-explanation {
        font-size: 0.88rem;
        line-height: 1.45;
        color: #9fb0d3;
        padding-bottom: 0.85rem;
        white-space: pre-wrap;
    }

    .player-profile-dl {
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .player-profile-row {
        display: grid;
        grid-template-columns: minmax(5.5rem, 7rem) 1fr;
        gap: 0.75rem 1rem;
        padding: 0.85rem 0;
        border-bottom: 1px solid var(--border);
        align-items: start;
    }

    .player-profile-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .player-profile-row:first-child {
        padding-top: 0;
    }

    .player-profile-label {
        margin: 0;
        color: var(--muted);
        font-size: 0.85rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-weight: 600;
    }

    .player-profile-value {
        margin: 0;
        color: #dce7ff;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .player-profile-bank {
        font-weight: 700;
        color: #eaf0ff;
        letter-spacing: 0.02em;
    }

    .player-profile-bank-formula {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem 0.55rem;
        line-height: 1.4;
    }

    .player-profile-bank-term {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .player-profile-bank-amount {
        font-size: 1.2rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.02em;
        color: #eaf0ff;
    }

    .player-profile-bank-term--result-pos .player-profile-bank-amount {
        color: var(--ok);
    }

    .player-profile-bank-term--result-neg .player-profile-bank-amount {
        color: #ff9a9a;
    }

    .player-profile-bank-term--balance-pos .player-profile-bank-amount {
        color: var(--ok);
    }

    .player-profile-bank-term--balance-neg .player-profile-bank-amount {
        color: #ff9a9a;
    }

    .player-profile-bank-op {
        color: var(--muted);
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1;
        user-select: none;
    }

    .player-profile-bank-formula .metric-info-icon {
        width: 15px;
        height: 15px;
    }

    .player-profile-tagline {
        color: #8ab7ff;
        font-weight: 600;
    }

    .player-profile-bio {
        white-space: pre-wrap;
    }

    .player-profile-avatar {
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 50%;
        object-fit: cover;
        display: block;
        border: 1px solid rgba(93, 226, 255, 0.25);
    }

    @media (max-width: 32rem) {
        .player-profile-row {
            grid-template-columns: 1fr;
            gap: 0.35rem;
        }
    }

    .player-stats-table-primary {
        color: #dce7ff;
    }

    .player-stats-table-primary-strong {
        color: #eaf0ff;
    }

    .player-stats-table-muted {
        color: var(--muted);
    }

    .player-stats-table-accent {
        color: #8bffcd;
    }

    .player-stats-result-value {
        color: var(--muted);
    }

    .player-stats-result-value--pos {
        color: var(--ok);
    }

    .player-stats-result-value--neg {
        color: #ff9a9a;
    }

    .player-stats-result-value--neutral {
        color: var(--muted);
    }

    .players-table-name {
        color: #dce7ff;
    }

    .players-table-value {
        color: #eaf0ff;
    }

    .players-table-muted {
        color: var(--muted);
    }

    .players-table-muted--small {
        font-size: 0.875rem;
    }

    .players-table-result {
        color: var(--muted);
    }

    .players-table-result--pos {
        color: var(--ok);
    }

    .players-table-result--neg {
        color: #ff9a9a;
    }

    .players-table-result--neutral {
        color: var(--muted);
    }

    /* Buttons + form controls (used by place-bet page) */
    .btn {
        appearance: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.6rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid var(--border);
        color: var(--text);
        background: rgba(12, 21, 36, 0.65);
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: 0.15s ease;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
    }

    .btn:hover {
        transform: translateY(-1px);
        background: rgba(93, 226, 255, 0.08);
    }

    .btn:active {
        transform: translateY(0);
    }

    .btn-primary {
        border-color: rgba(93, 226, 255, 0.45);
        background: linear-gradient(135deg, rgba(93, 226, 255, 0.22), rgba(138, 123, 255, 0.16));
    }

    .btn-secondary {
        border-color: rgba(159, 176, 211, 0.35);
        background: rgba(12, 21, 36, 0.45);
        color: #d6e4ff;
    }

    .btn:disabled,
    .btn[disabled] {
        opacity: 0.45;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .btn:disabled:hover,
    .btn[disabled]:hover {
        transform: none;
        background: rgba(12, 21, 36, 0.45);
    }

    /* Subscribe plans */
    .subscribe-plans-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 0.5rem;
    }

    @media (max-width: 900px) {
        .subscribe-plans-grid {
            grid-template-columns: 1fr;
        }
    }

    .subscribe-plan-card {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        border-radius: 1rem;
        border: 1px solid rgba(130, 162, 255, 0.22);
        background: linear-gradient(180deg, rgba(17, 27, 46, 0.92), rgba(10, 16, 30, 0.88));
        padding: 1.25rem 1.25rem 1.1rem;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.22);
    }

    .subscribe-plan-card--featured {
        border-color: rgba(93, 226, 255, 0.45);
        box-shadow: 0 18px 48px rgba(93, 226, 255, 0.08);
    }

    .subscribe-plan-card--disabled {
        opacity: 0.72;
    }

    .subscribe-plan-name {
        margin: 0 0 0.35rem;
        font-size: 1.15rem;
        font-weight: 700;
        color: #eaf0ff;
    }

    .subscribe-plan-duration {
        margin: 0 0 0.5rem;
        font-size: 0.9rem;
        color: #9fb0d3;
    }

    .subscribe-plan-price {
        margin: 0 0 1rem;
        font-size: 1.35rem;
        font-weight: 700;
        color: #e8f0ff;
    }

    .subscribe-payment-card {
        max-width: 420px;
    }

    .subscribe-payment-stub {
        margin: 1rem 0 0;
        font-size: 0.95rem;
        color: #9fb0d3;
    }

    .subscribe-stripe-payment {
        margin-top: 1.25rem;
    }

    .subscribe-stripe-element {
        margin-bottom: 1rem;
    }

    .subscribe-stripe-submit {
        width: 100%;
    }

    .subscribe-payment-message {
        margin: 0 0 0.75rem;
        font-size: 0.9rem;
        color: #ff8b9a;
    }

    .subscribe-payment-message[hidden] {
        display: none;
    }

    .subscribe-terms-card {
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .subscribe-terms-body {
        max-height: 22rem;
        overflow-y: auto;
        margin-bottom: 1.25rem;
        padding-right: 0.5rem;
        font-size: 0.92rem;
        line-height: 1.55;
        color: #c8d6f5;
    }

    .subscribe-terms-body h2 {
        margin: 1.25rem 0 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: #eaf0ff;
    }

    .subscribe-terms-body h2:first-child {
        margin-top: 0;
    }

    .subscribe-terms-body p {
        margin: 0 0 0.75rem;
    }

    .legal-page-card {
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .legal-page-body {
        font-size: 0.95rem;
        line-height: 1.6;
        color: #c8d6f5;
    }

    .legal-page-body h2 {
        margin: 1.5rem 0 0.65rem;
        font-size: 1.05rem;
        font-weight: 700;
        color: #eaf0ff;
    }

    .legal-page-body h2:first-child {
        margin-top: 0;
    }

    .legal-page-body p {
        margin: 0 0 0.85rem;
    }

    .legal-page-body ul,
    .legal-page-body ol {
        margin: 0 0 0.85rem 1.25rem;
        padding: 0;
    }

    .subscribe-terms-checkbox {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        margin-bottom: 1rem;
        font-size: 0.92rem;
        color: #dce7ff;
        cursor: pointer;
    }

    .subscribe-terms-checkbox input {
        margin-top: 0.2rem;
        flex-shrink: 0;
    }

    .subscribe-terms-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    .subscribe-payment-methods {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid rgba(130, 162, 255, 0.2);
    }

    .subscribe-payment-methods-title {
        margin: 0 0 0.75rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: #c7d7fa;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .subscribe-crypto-wallet-list {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .subscribe-crypto-wallet-link {
        width: 100%;
        justify-content: center;
    }

    .subscribe-crypto-details {
        margin: 1rem 0;
    }

    .subscribe-crypto-detail-row {
        margin-bottom: 0.85rem;
    }

    .subscribe-crypto-detail-row dt {
        margin: 0 0 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #9fb0d3;
    }

    .subscribe-crypto-detail-row dd {
        margin: 0;
        color: #eaf0ff;
    }

    .subscribe-crypto-code {
        display: inline-block;
        padding: 0.35rem 0.55rem;
        border-radius: 0.4rem;
        background: rgba(6, 11, 22, 0.65);
        border: 1px solid rgba(130, 162, 255, 0.25);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.88rem;
        word-break: break-all;
    }

    .subscribe-crypto-code--emphasis {
        color: #8bffcd;
        font-weight: 700;
    }

    .subscribe-crypto-instructions {
        margin: 0 0 1rem;
        font-size: 0.92rem;
        line-height: 1.5;
        color: #c8d6f5;
    }

    .subscribe-crypto-status {
        margin: 0;
        font-size: 0.92rem;
        color: #ffd666;
    }

    .subscribe-crypto-status--ok {
        color: var(--ok);
    }

    .subscribe-crypto-paid-form {
        margin-top: 0.5rem;
    }

    .subscribe-plan-features {
        margin: 0 0 1.25rem;
        padding: 0;
        list-style: none;
        flex: 1;
    }

    .subscribe-plan-features li {
        margin: 0 0 0.45rem;
        font-size: 0.88rem;
        color: #c8d6f5;
    }

    .subscribe-plan-features li::before {
        content: "✓ ";
        color: var(--accent);
    }

    .subscribe-plan-action {
        margin-top: auto;
    }

    .subscribe-plan-badge {
        display: inline-block;
        margin-bottom: 0.65rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #8bffcd;
        border: 1px solid rgba(139, 255, 205, 0.35);
        background: rgba(139, 255, 205, 0.08);
    }

    .input-dark {
        width: 100%;
        border-radius: 0.75rem;
        border: 1px solid rgba(130, 162, 255, 0.25);
        background: rgba(6, 11, 22, 0.75);
        color: var(--text);
        padding: 0.7rem 0.85rem;
        outline: none;
        font-size: 1rem;
    }

    .input-dark:focus {
        border-color: rgba(93, 226, 255, 0.55);
        box-shadow: 0 0 0 4px rgba(93, 226, 255, 0.12);
    }

    .bet-kpi {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        margin-top: 12px;
        padding: 0.9rem 1rem;
        border: 1px solid var(--border);
        border-radius: 0.9rem;
        background: rgba(12, 21, 36, 0.45);
    }

    .bet-kpi .label {
        color: var(--muted);
        font-size: 0.9rem;
    }

    .bet-kpi .value {
        font-weight: 800;
        color: #8bffcd;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.01em;
    }

    .flash {
        border: 1px solid rgba(76, 255, 157, 0.35);
        color: var(--ok);
        background: rgba(76, 255, 157, 0.08);
        border-radius: 0.9rem;
        padding: 0.9rem 1rem;
    }

    .welcome-top-bettors {
        margin: 1rem 0 1.35rem;
    }

    .welcome-top-bettors-title {
        margin: 0 0 0.35rem;
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text);
        letter-spacing: 0.01em;
    }

    .welcome-top-bettors-lead {
        margin: 0 0 1rem;
        color: var(--muted);
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .welcome-top-bettors-grid {
        display: grid;
        gap: 0.85rem;
        grid-template-columns: repeat(3, 1fr);
    }

    @media (max-width: 767px) {
        .welcome-top-bettors-grid {
            grid-template-columns: 1fr;
        }
    }

    .welcome-bettor-card {
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        padding: 1rem 1.1rem;
        border: 1px solid var(--border);
        border-radius: 0.95rem;
        background: var(--surface);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.22);
    }

    a.welcome-bettor-card-link {
        text-decoration: none;
        color: inherit;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    a.welcome-bettor-card-link:hover {
        border-color: rgba(93, 226, 255, 0.45);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.28);
    }

    a.welcome-bettor-card-link:focus-visible {
        outline: 2px solid rgba(93, 226, 255, 0.85);
        outline-offset: 2px;
    }

    .welcome-bettor-card-avatar {
        flex-shrink: 0;
    }

    .welcome-bettor-card-avatar-img {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        object-fit: cover;
        display: block;
        border: 1px solid rgba(93, 226, 255, 0.25);
    }

    .welcome-bettor-card-avatar-placeholder {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--accent);
        background: rgba(93, 226, 255, 0.1);
        border: 1px solid rgba(93, 226, 255, 0.35);
        line-height: 1;
    }

    .welcome-bettor-card-rank {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 700;
        color: var(--accent);
        border: 1px solid rgba(93, 226, 255, 0.35);
        border-radius: 0.5rem;
        background: rgba(93, 226, 255, 0.08);
    }

    .welcome-bettor-card-body {
        min-width: 0;
        flex: 1;
    }

    .welcome-bettor-card-name {
        margin: 0 0 0.4rem;
        font-size: 1.02rem;
        font-weight: 600;
        color: var(--text);
        line-height: 1.25;
        word-break: break-word;
    }

    .welcome-bettor-card-bets-meta {
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 0.2rem 0.45rem;
        font-size: 0.95rem;
        line-height: 1.35;
    }

    .welcome-bettor-card-bets-count {
        font-weight: 600;
        color: var(--muted);
    }

    .welcome-bettor-card-bets-sep {
        color: var(--muted);
        opacity: 0.65;
        user-select: none;
    }

    .welcome-bettor-card-bets-stake {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: #eaf0ff;
        letter-spacing: 0.02em;
    }

    .welcome-bettor-card-result {
        margin: 0.4rem 0 0;
        font-size: 1.05rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.02em;
        line-height: 1.25;
    }

    .welcome-bettor-card-result--pos {
        color: var(--ok);
    }

    .welcome-bettor-card-result--neg {
        color: #ff9a9a;
    }

    .welcome-bettor-card-form {
        margin-top: 0.5rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.15rem 0.1rem;
    }

    .welcome-see-all-players {
        margin: -0.35rem 0 1.35rem;
        text-align: right;
    }

    .welcome-featured-bets {
        margin-top: 0;
    }

    .welcome-bettor-card--won {
        border-color: rgba(76, 217, 100, 0.45);
    }

    .welcome-bettor-card--lost {
        border-color: rgba(255, 120, 120, 0.4);
    }

    .welcome-bettor-card--void {
        border-color: rgba(255, 200, 87, 0.4);
    }

    a.welcome-featured-bet-player-link,
    a.welcome-featured-bet-event-link {
        color: inherit;
        text-decoration: none;
    }

    a.welcome-featured-bet-player-link:hover,
    a.welcome-featured-bet-event-link:hover {
        color: var(--accent);
        text-decoration: underline;
    }

    .tournament-leagues-line {
        padding: 0.85rem 1rem;
    }

    .tournament-leagues-line-inner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem 0.5rem;
    }

    .tournament-league-link {
        color: var(--accent);
        text-decoration: none;
        font-weight: 600;
    }

    .tournament-league-link:hover {
        text-decoration: underline;
    }

    .tournament-league-sep {
        color: var(--muted);
        user-select: none;
    }

    .form-icons-cell {
        white-space: nowrap;
    }

    .form-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.35rem;
        height: 1.35rem;
        margin-right: 0.2rem;
        margin-bottom: 0.15rem;
        padding: 0 0.2rem;
        border-radius: 0.3rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        cursor: default;
        line-height: 1;
    }

    .form-icon--w {
        color: #0d2818;
        background: rgba(76, 255, 157, 0.85);
    }

    .form-icon--l {
        color: #fff5f5;
        background: rgba(220, 53, 69, 0.75);
    }

    .form-icon--d {
        color: #1a1408;
        background: rgba(255, 214, 102, 0.9);
    }

    .form-icon--muted {
        color: var(--muted);
        background: rgba(159, 176, 211, 0.12);
        border: 1px solid rgba(159, 176, 211, 0.25);
        font-size: 0.62rem;
        font-weight: 700;
    }

    /* Standings promotion / relegation row bands (see Tournament.standings_promrel) */
    tr.standings-row--promotion > td,
    tr.standings-row--relegation > td {
        border-top: 1px solid transparent;
        border-bottom: 1px solid transparent;
    }

    tr.standings-row--promotion-cl > td {
        background: linear-gradient(90deg, rgba(255, 214, 102, 0.22), rgba(93, 226, 255, 0.12));
        border-color: rgba(255, 214, 102, 0.35);
    }

    tr.standings-row--promotion-el > td {
        background: linear-gradient(90deg, rgba(255, 165, 92, 0.2), rgba(138, 123, 255, 0.1));
        border-color: rgba(255, 165, 92, 0.35);
    }

    tr.standings-row--promotion-cel > td {
        background: linear-gradient(90deg, rgba(76, 255, 157, 0.14), rgba(93, 226, 255, 0.08));
        border-color: rgba(76, 255, 157, 0.28);
    }

    tr.standings-row--promotion-other > td {
        background: rgba(138, 123, 255, 0.12);
        border-color: rgba(138, 123, 255, 0.25);
    }

    tr.standings-row--relegation > td {
        background: linear-gradient(90deg, rgba(255, 99, 99, 0.16), rgba(255, 99, 99, 0.06));
        border-color: rgba(255, 99, 99, 0.28);
    }

    .standings-pos-cell {
        vertical-align: middle;
    }

    .standings-pos-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.75rem;
        height: 1.75rem;
        padding: 0 0.35rem;
        border-radius: 0.45rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        line-height: 1;
        cursor: default;
        box-sizing: border-box;
        border: 2px solid transparent;
    }

    .standings-pos-badge--cl {
        color: #1a1408;
        background: rgba(255, 214, 102, 0.35);
        border-color: rgba(255, 193, 7, 0.85);
        box-shadow: 0 0 0 1px rgba(93, 226, 255, 0.2);
    }

    .standings-pos-badge--el {
        color: #2a1810;
        background: rgba(255, 165, 92, 0.35);
        border-color: rgba(255, 140, 66, 0.9);
    }

    .standings-pos-badge--cel {
        color: #0d2818;
        background: rgba(76, 255, 157, 0.28);
        border-color: rgba(76, 255, 157, 0.75);
    }

    .standings-pos-badge--promo-other {
        color: #eaf0ff;
        background: rgba(138, 123, 255, 0.35);
        border-color: rgba(167, 139, 250, 0.85);
    }

    .standings-pos-badge--rel {
        color: #fff5f5;
        background: rgba(220, 53, 69, 0.45);
        border-color: rgba(255, 99, 99, 0.95);
    }

    .standings-team-cell {
        white-space: nowrap;
    }

    .standings-movement {
        margin-left: 0.28rem;
        font-weight: 800;
        font-size: 0.92em;
        vertical-align: middle;
        line-height: 1;
    }

    .standings-movement--up {
        color: #1a7f3e;
    }

    .standings-movement--down {
        color: #c92a2a;
    }

    .standings-groups {
        display: grid;
        gap: 1.75rem;
        margin-top: 0.75rem;
    }

    .standings-group {
        min-width: 0;
    }

    .standings-group-title {
        margin: 0 0 0.65rem;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text);
    }

    .standings-group .overflow-x-auto,
    .standings-groups .overflow-x-auto {
        overflow-x: auto;
    }

    .footer-cookie-settings {
        padding: 0 0 1.25rem;
        text-align: center;
    }

    .footer-cookie-settings-link {
        background: none;
        border: none;
        color: var(--muted);
        font-size: 0.88rem;
        text-decoration: underline;
        cursor: pointer;
        padding: 0;
    }

    .footer-cookie-settings-link:hover {
        color: var(--text);
    }

    .cookie-consent-banner {
        position: fixed;
        inset: auto 0 0 0;
        z-index: 1200;
        padding: 1rem;
    }

    .cookie-consent-banner-inner.card {
        max-width: 56rem;
        margin: 0 auto;
        padding: 1.1rem 1.25rem;
        background: var(--cookie-consent-bg);
        border: 1px solid var(--border);
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
    }

    .cookie-consent-title {
        margin: 0 0 0.5rem;
        font-size: 1.1rem;
        font-weight: 700;
        color: #eaf0ff;
    }

    .cookie-consent-text {
        margin: 0 0 1rem;
        font-size: 0.92rem;
        line-height: 1.55;
        color: #c8d6f5;
    }

    .cookie-consent-link {
        color: var(--accent);
        text-decoration: underline;
    }

    .cookie-consent-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        justify-content: flex-end;
    }

    .cookie-consent-modal {
        position: fixed;
        inset: 0;
        z-index: 1300;
        display: grid;
        place-items: center;
        padding: 1rem;
    }

    .cookie-consent-modal[hidden],
    .cookie-consent-banner[hidden] {
        display: none !important;
    }

    .cookie-consent-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(4, 10, 22, 0.72);
    }

    .cookie-consent-modal-panel.card {
        position: relative;
        width: min(100%, 36rem);
        max-height: calc(100vh - 2rem);
        overflow: auto;
        background: var(--cookie-consent-bg);
        border: 1px solid var(--border);
    }

    .cookie-consent-mode {
        margin: 0 0 1rem;
        padding: 0;
        border: 0;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .cookie-consent-mode-legend {
        margin: 0 0 0.35rem;
        font-size: 0.92rem;
        font-weight: 700;
        color: #eaf0ff;
    }

    .cookie-consent-mode-option {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        font-size: 0.92rem;
        color: #dce7ff;
        cursor: pointer;
    }

    .cookie-consent-category-list {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        margin-bottom: 1.1rem;
    }

    .cookie-consent-category-list--locked {
        opacity: 0.72;
    }

    .cookie-consent-category {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        cursor: pointer;
    }

    .cookie-consent-category input {
        margin-top: 0.2rem;
        flex-shrink: 0;
    }

    .cookie-consent-category-copy {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .cookie-consent-category-label {
        font-weight: 700;
        color: #eaf0ff;
    }

    .cookie-consent-category-description {
        font-size: 0.88rem;
        line-height: 1.45;
        color: #b7c8ea;
    }
</style>
@endif
