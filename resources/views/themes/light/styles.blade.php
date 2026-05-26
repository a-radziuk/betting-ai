@include('layouts.partials.betai-styles', ['theme' => 'default'])

<style data-theme="light">
    :root {
        --bg: #cfe0f4;
        --bg-soft: #deebf8;
        --surface: rgba(248, 252, 255, 0.88);
        --border: rgba(86, 135, 214, 0.24);
        --text: #10263d;
        --muted: #526a86;
        --accent: #2f98f1;
        --accent2: #7a8cff;
        --ok: #14895d;
    }

    body {
        color: var(--text);
        background:
            repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0 2px, rgba(255, 255, 255, 0) 2px 20px),
            repeating-linear-gradient(45deg, rgba(47, 152, 241, 0.035) 0 1px, rgba(47, 152, 241, 0) 1px 16px),
            radial-gradient(circle at 12% 12%, rgba(47, 152, 241, 0.18), transparent 32%),
            radial-gradient(circle at 88% 6%, rgba(122, 140, 255, 0.17), transparent 28%),
            linear-gradient(180deg, #e0eaf6 0%, #cfe0f4 48%, #c3d7ee 100%);
    }

    .header {
        background: rgba(236, 245, 255, 0.84);
        border-bottom-color: rgba(86, 135, 214, 0.18);
        box-shadow: 0 10px 30px rgba(72, 118, 170, 0.1);
    }

    .subbar {
        background: rgba(229, 240, 252, 0.8);
        border-bottom-color: rgba(86, 135, 214, 0.16);
    }

    .logo,
    .event-tip-card-name,
    .welcome-bettor-card-name,
    .market .name,
    .player-profile-bank,
    .admin-page-title,
    .hero h1,
    .hero h2 {
        color: var(--text);
    }

    .logo-text {
        background: linear-gradient(135deg, #18324d, #2f7fe4 45%, #53c6ff 80%, #7a8cff);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .header-link,
    .btn,
    .btn-secondary,
    .admin-sidebar-link,
    .event-tip-card-pick-box,
    .bet-kpi,
    .input-dark,
    .admin-upload-textarea,
    .admin-resolve-score-input,
    .admin-abandon-textarea {
        color: var(--text);
        border-color: rgba(86, 135, 214, 0.2);
        background: rgba(245, 250, 255, 0.76);
        box-shadow: 0 10px 30px rgba(79, 130, 188, 0.09);
    }

    .header-link:hover,
    .btn:hover,
    .admin-sidebar-link:hover {
        background: rgba(47, 152, 241, 0.09);
    }

    .btn-primary {
        border-color: rgba(47, 152, 241, 0.28);
        background: linear-gradient(135deg, rgba(47, 152, 241, 0.18), rgba(122, 140, 255, 0.18));
    }

    .card,
    .event-tip-card,
    .event-analysis-card,
    .market,
    .event-empty,
    .user-results-item,
    .welcome-bettor-card,
    .subscribe-plan-card,
    .admin-sidebar {
        background: rgba(244, 249, 255, 0.9);
        border-color: rgba(86, 135, 214, 0.18);
        box-shadow: 0 18px 45px rgba(70, 130, 180, 0.09);
    }

    thead th,
    .market-head {
        color: #425d78;
        background: rgba(219, 232, 248, 0.92);
        border-bottom-color: rgba(86, 135, 214, 0.16);
    }

    tbody td,
    .market .row,
    .event-tip-card-head,
    .event-analysis-head,
    .event-analysis-influenced,
    .player-profile-row,
    .admin-table th,
    .admin-table td,
    .tournament-section-head {
        border-color: rgba(86, 135, 214, 0.12);
    }

    tbody tr:hover,
    .admin-sidebar-link--active {
        background: rgba(47, 152, 241, 0.08);
    }

    .admin-sidebar-link--active {
        border: 1px solid rgba(47, 152, 241, 0.2);
    }

    .hero p,
    .hero .meta,
    .header-tag,
    .empty,
    .admin-empty,
    .admin-page-meta,
    .welcome-top-bettors-lead,
    .welcome-bettor-card-bets-count,
    .welcome-events-section-title,
    .player-profile-label,
    .player-profile-value,
    .player-current-bet-explanation,
    .player-stats-table-muted,
    .event-analysis-metric-label,
    .event-analysis-outcome-label,
    .event-analysis-goals-label,
    .event-analysis-influenced-title,
    .user-results-label,
    .user-results-in-play-meta,
    .user-results-metric-label,
    .metric-info,
    .event-tip-card-pick-row dt,
    .admin-table-sub,
    .dashboard-pagination .text-gray-700,
    .footer-inner,
    footer {
        color: var(--muted) !important;
    }

    .welcome-odds,
    .event-tip-card-pick-row dd,
    .event-analysis-description,
    .event-analysis-goals-value,
    .event-analysis-metric-value,
    .user-results-value,
    .user-results-metric-value,
    .welcome-bettor-card-bets-stake,
    .player-stats-table-primary,
    .player-stats-table-primary-strong,
    .players-table-name,
    .players-table-value,
    .admin-table,
    .text-\[\#dce7ff\],
    .text-\[\#eaf0ff\] {
        color: var(--text) !important;
    }

    .tournament-see-all-link,
    .tournament-league-link,
    .admin-table-link,
    .subbar-back,
    .event-tip-card-subscribe-link,
    .event-analysis-influenced-link {
        color: var(--accent);
    }

    .welcome-bettor-card-avatar-placeholder,
    .event-tip-card-avatar-placeholder,
    .welcome-bettor-card-rank {
        color: var(--accent);
        background: rgba(58, 167, 255, 0.1);
        border-color: rgba(58, 167, 255, 0.22);
    }

    .welcome-bettor-card-avatar-img,
    .event-tip-card-avatar-img,
    .player-profile-avatar {
        border-color: rgba(58, 167, 255, 0.2);
    }

    .welcome-tips-badge {
        color: #8a5c00;
        background: linear-gradient(145deg, rgba(255, 219, 117, 0.3), rgba(255, 170, 83, 0.22));
        border-color: rgba(255, 183, 77, 0.48);
        box-shadow:
            0 0 12px rgba(255, 193, 7, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.4);
    }

    .welcome-match-meta-tips,
    .welcome-events-section table.welcome-events-table .welcome-tips-col,
    .welcome-events-section table.welcome-events-table thead .welcome-tips-col {
        color: #b37700;
    }

    .flash,
    .admin-flash--success {
        color: #15734d;
        background: rgba(20, 137, 93, 0.08);
        border-color: rgba(20, 137, 93, 0.22);
    }

    .admin-flash--error,
    .btn-danger {
        color: #8f2333;
        background: rgba(220, 38, 38, 0.08);
        border-color: rgba(220, 38, 38, 0.22);
    }

    .btn-danger:hover {
        background: rgba(220, 38, 38, 0.14);
        border-color: rgba(220, 38, 38, 0.28);
        color: #8f2333;
    }

    .dashboard-pagination nav a,
    .dashboard-pagination nav span.inline-flex {
        background: rgba(255, 255, 255, 0.75) !important;
        color: var(--text) !important;
        border-color: rgba(100, 149, 237, 0.18) !important;
    }

    .dashboard-pagination nav span[aria-disabled] span,
    .dashboard-pagination nav span[aria-current] span {
        background: rgba(58, 167, 255, 0.1) !important;
        color: var(--text) !important;
    }

    .form-icon--w {
        color: #ffffff;
        background: rgba(20, 137, 93, 0.9);
    }

    .form-icon--l {
        color: #ffffff;
        background: rgba(220, 53, 69, 0.76);
    }

    .form-icon--d {
        color: #6d4a00;
        background: rgba(255, 214, 102, 0.88);
    }

    .form-icon--muted {
        color: #6b7f96;
        background: rgba(103, 129, 158, 0.1);
        border-color: rgba(103, 129, 158, 0.18);
    }

    .user-results-chart-line {
        stroke: var(--accent);
    }

    .user-results-chart-dot {
        fill: var(--accent);
        stroke: #ffffff;
    }

    .user-results-chart-point:hover .user-results-chart-dot,
    .user-results-chart-point:focus-visible .user-results-chart-dot {
        fill: #18a96f;
        stroke: #ffffff;
    }

    .user-results-chart-tooltip-bg,
    .metric-info-tooltip {
        background: rgba(255, 255, 255, 0.96);
        fill: rgba(255, 255, 255, 0.96);
        stroke: rgba(58, 167, 255, 0.22);
        border-color: rgba(58, 167, 255, 0.22);
        box-shadow: 0 8px 24px rgba(79, 130, 188, 0.08);
        color: var(--text);
    }

    .user-results-chart-tooltip-text {
        fill: var(--text);
    }

    .player-profile-bank-amount {
        color: var(--text);
    }

    .player-profile-bank-op {
        color: var(--muted);
    }

    .metric-info:hover,
    .metric-info:focus-visible,
    a.welcome-bettor-card-link:hover .welcome-bettor-card-name,
    a.event-tip-card-name:hover {
        color: var(--accent);
    }

    .subscribe-plan-card {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(241, 247, 255, 0.9));
    }

    .subscribe-plan-card--featured {
        border-color: rgba(58, 167, 255, 0.26);
        box-shadow: 0 18px 48px rgba(58, 167, 255, 0.08);
    }

    .subscribe-plan-name,
    .event-analysis-outcome-value,
    .event-tip-card-odds,
    .bet-kpi .value,
    .player-stats-table-accent,
    .text-\[\#8bffcd\] {
        color: var(--accent) !important;
    }

    .subscribe-plan-badge {
        color: #15734d;
        border-color: rgba(20, 137, 93, 0.25);
        background: rgba(20, 137, 93, 0.08);
    }

    .subscribe-plan-duration {
        color: #3f5d79 !important;
    }

    .subscribe-plan-features li {
        color: #1c3954 !important;
    }

    .status,
    .bet-status--won,
    .event-tip-card-result--pos,
    .event-tip-card-efficiency--pos,
    .player-stats-result-value--pos,
    .player-profile-bank-term--result-pos .player-profile-bank-amount,
    .player-profile-bank-term--balance-pos .player-profile-bank-amount,
    .welcome-bettor-card-result--pos,
    .user-results-chart-dot--origin {
        color: var(--ok) !important;
    }

    .player-stats-result-value--neg {
        color: #c45466 !important;
    }

    .bet-status--lost,
    .event-tip-card-result--neg,
    .event-tip-card-efficiency--neg,
    .player-profile-bank-term--result-neg .player-profile-bank-amount,
    .player-profile-bank-term--balance-neg .player-profile-bank-amount,
    .players-table-result--neg,
    .welcome-bettor-card-result--neg {
        color: #c45466 !important;
    }

    .player-stats-result-value--neutral {
        color: var(--muted) !important;
    }

    .bet-status,
    .bet-status--pending,
    .bet-status--void,
    .bet-status--cancelled {
        background: rgba(255, 255, 255, 0.7);
    }
</style>
