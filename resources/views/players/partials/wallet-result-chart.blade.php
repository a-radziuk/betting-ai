@php
    /** @var \App\Support\PlayerWalletResultChart $resultChart */
    /** @var \App\Models\User|null $player */
    $latest = $resultChart->latest;
    $isFullHistory = $isFullHistory ?? false;
    $showFullTrendLink = ($showFullTrendLink ?? true) && isset($player);
    $formatChartValue = static function (float $value, bool $isOrigin = false): string {
        if ($isOrigin) {
            return '0.00';
        }

        return ($value > 0 ? '+' : '').number_format($value, 2);
    };
    $betCount = count($resultChart->values);
@endphp

<span class="user-results-item user-results-item--chart">
    @if ($showFullTrendLink)
        <div class="player-result-head user-results-chart-head">
            <span class="user-results-label">{{ __('Result trend') }}</span>
            <a href="{{ route('players.result-trend', ['user' => $player->id]) }}" class="user-results-chart-full-link subbar-back">
                {{ __('View full trend') }}
            </a>
        </div>
    @else
        <span class="user-results-label">{{ __('Result trend') }}</span>
    @endif
    @if ($resultChart->hasData())
        <span @class([
            'user-results-value',
            'user-results-chart-latest',
            'player-stats-result-value',
            'player-stats-result-value--pos' => ($latest ?? 0) > 0.000001,
            'player-stats-result-value--neg' => ($latest ?? 0) < -0.000001,
            'player-stats-result-value--neutral' => $latest === null || abs($latest) <= 0.000001,
        ])>
            {{ $formatChartValue($latest) }}
        </span>
        <svg
            @class(['user-results-chart', 'user-results-chart--full' => $isFullHistory])
            viewBox="0 0 100 40"
            preserveAspectRatio="none"
            role="img"
            aria-label="{{ $isFullHistory
                ? __('Cumulative result over all :count resolved bets', ['count' => $betCount])
                : __('Cumulative result over the last :count resolved bets', ['count' => $betCount]) }}"
        >
            @if ($resultChart->zeroLineY !== null)
                <line
                    x1="4"
                    y1="{{ $resultChart->zeroLineY }}"
                    x2="96"
                    y2="{{ $resultChart->zeroLineY }}"
                    class="user-results-chart-zero"
                />
            @endif
            <polyline
                points="{{ $resultChart->polylinePoints }}"
                fill="none"
                class="user-results-chart-line"
            />
            @foreach ($resultChart->points as $point)
                @php
                    $isOrigin = $point['isOrigin'] ?? false;
                    $label = $formatChartValue($point['value'], $isOrigin);
                    $tooltipY = $point['y'] > 10 ? $point['y'] - 5.5 : $point['y'] + 7.5;
                    $tooltipWidth = max(22, strlen($label) * 2.35);
                    $tooltipHalf = $tooltipWidth / 2;
                @endphp
                <g @class(['user-results-chart-point', 'user-results-chart-point--origin' => $isOrigin]) tabindex="0">
                    <ellipse
                        cx="{{ $point['x'] }}"
                        cy="{{ $point['y'] }}"
                        rx="6"
                        ry="6"
                        class="user-results-chart-hit"
                        data-chart-radius="6"
                    />
                    <ellipse
                        cx="{{ $point['x'] }}"
                        cy="{{ $point['y'] }}"
                        rx="2.25"
                        ry="2.25"
                        @class(['user-results-chart-dot', 'user-results-chart-dot--origin' => $isOrigin])
                        data-chart-radius="2.25"
                    />
                    <g class="user-results-chart-tooltip" transform="translate({{ $point['x'] }}, {{ $tooltipY }})">
                        <rect
                            class="user-results-chart-tooltip-bg"
                            x="{{ -$tooltipHalf }}"
                            y="-6.5"
                            width="{{ $tooltipWidth }}"
                            height="7"
                            rx="1.5"
                        />
                        <text
                            class="user-results-chart-tooltip-text"
                            text-anchor="middle"
                            dominant-baseline="middle"
                            y="-3"
                        >{{ $label }}</text>
                    </g>
                    <title>{{ $label }}</title>
                </g>
            @endforeach
        </svg>
        <span class="user-results-chart-caption">
            {{ $isFullHistory
                ? __('All :count resolved bets', ['count' => $betCount])
                : __('Last :count resolved bets', ['count' => $betCount]) }}
        </span>
    @else
        <p class="user-results-chart-empty">{{ __('Not enough resolved bets for a chart yet.') }}</p>
    @endif
</span>
