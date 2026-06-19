@php
    /** @var \App\Support\PlayerWalletResultChart $resultChart */
    /** @var \App\Models\User|null $player */
    $latest = $resultChart->latest;
    $isFullHistory = $isFullHistory ?? false;
    $showFullTrendLink = ($showFullTrendLink ?? true) && isset($player);
    $showChartDates = $showChartDates ?? false;
    $formatChartValue = static function (float $value, bool $isOrigin = false): string {
        if ($isOrigin) {
            return '0.00';
        }

        return ($value > 0 ? '+' : '').number_format($value, 2);
    };
    $betCount = count($resultChart->values);
    $chartPointItems = [];

    foreach ($resultChart->points as $index => $point) {
        $isOrigin = $point['isOrigin'] ?? false;
        $label = $formatChartValue($point['value'], $isOrigin);

        $chartPointItems[] = [
            'index' => $index,
            'point' => $point,
            'isOrigin' => $isOrigin,
            'label' => $label,
            'tooltipY' => $point['y'] - 6.5,
            'tooltipWidth' => max(22, strlen($label) * 2.35),
        ];
    }

    $chartDotRadius = $isFullHistory ? 1.5 : 2.25;
    $chartHitRadius = $isFullHistory ? 4.5 : 6;
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
        <div @class(['user-results-chart-wrap', 'user-results-chart-wrap--dates' => $showChartDates])>
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
            <g class="user-results-chart-points">
                @foreach ($chartPointItems as $item)
                    <g
                        @class(['user-results-chart-point', 'user-results-chart-point--origin' => $item['isOrigin']])
                        data-chart-point="{{ $item['index'] }}"
                        tabindex="0"
                        aria-label="{{ $item['label'] }}"
                    >
                        <ellipse
                            cx="{{ $item['point']['x'] }}"
                            cy="{{ $item['point']['y'] }}"
                            rx="{{ $chartHitRadius }}"
                            ry="{{ $chartHitRadius }}"
                            class="user-results-chart-hit"
                            data-chart-radius="{{ $chartHitRadius }}"
                        />
                        <ellipse
                            cx="{{ $item['point']['x'] }}"
                            cy="{{ $item['point']['y'] }}"
                            rx="{{ $chartDotRadius }}"
                            ry="{{ $chartDotRadius }}"
                            @class(['user-results-chart-dot', 'user-results-chart-dot--origin' => $item['isOrigin']])
                            data-chart-radius="{{ $chartDotRadius }}"
                        />
                    </g>
                @endforeach
            </g>
            <g class="user-results-chart-tooltips" aria-hidden="true">
                @foreach ($chartPointItems as $item)
                    @php
                        $tooltipHalf = $item['tooltipWidth'] / 2;
                    @endphp
                    <g
                        class="user-results-chart-tooltip"
                        data-chart-point="{{ $item['index'] }}"
                        transform="translate({{ $item['point']['x'] }}, {{ $item['tooltipY'] }})"
                    >
                        <rect
                            class="user-results-chart-tooltip-bg"
                            x="{{ -$tooltipHalf }}"
                            y="-6.5"
                            width="{{ $item['tooltipWidth'] }}"
                            height="7"
                            rx="1.5"
                        />
                        <text
                            class="user-results-chart-tooltip-text"
                            text-anchor="middle"
                            dominant-baseline="middle"
                            y="-3"
                        >{{ $item['label'] }}</text>
                    </g>
                @endforeach
            </g>
        </svg>
        @if ($showChartDates)
            <div class="user-results-chart-axis" aria-hidden="true">
                @foreach ($resultChart->axisDateLabels() as $axisLabel)
                    <span
                        @class([
                            'user-results-chart-axis-label',
                            'user-results-chart-axis-label--start' => $axisLabel['align'] === 'start',
                            'user-results-chart-axis-label--end' => $axisLabel['align'] === 'end',
                        ])
                        @if ($axisLabel['align'] === 'center')
                            style="left: {{ $axisLabel['x'] }}%"
                        @elseif ($axisLabel['align'] === 'start')
                            style="left: {{ $axisLabel['x'] }}%"
                        @else
                            style="right: {{ 100 - $axisLabel['x'] }}%"
                        @endif
                    >{{ $axisLabel['date'] }}</span>
                @endforeach
            </div>
        @endif
        </div>
        <span class="user-results-chart-caption">
            {{ $isFullHistory
                ? __('All :count resolved bets', ['count' => $betCount])
                : __('Last :count resolved bets', ['count' => $betCount]) }}
        </span>
    @else
        <p class="user-results-chart-empty">{{ __('Not enough resolved bets for a chart yet.') }}</p>
    @endif
</span>
