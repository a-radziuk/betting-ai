@if ($eventAnalysis)
    <section class="event-analysis-section" aria-labelledby="event-analysis-title">
        <h2 id="event-analysis-title" class="event-analysis-title">Match analysis</h2>
        <article class="event-analysis-card">
            <header class="event-analysis-head">
                <div class="event-analysis-outcome">
                    <span class="event-analysis-outcome-label">Likely outcome</span>
                    <strong class="event-analysis-outcome-value">{{ $eventAnalysis->likelyOutcomeLabel() }}</strong>
                </div>
                <div class="event-analysis-goals">
                    <span class="event-analysis-goals-label">Approx. goals</span>
                    <strong class="event-analysis-goals-value">{{ $eventAnalysis->approximate_goals }}</strong>
                </div>
                <span class="event-analysis-strength" title="Analysis strength">
                    Strength {{ $eventAnalysis->strength }}/{{ \App\Models\EventAnalysis::STRENGTH_MAX }}
                </span>
            </header>

            <p class="event-analysis-description">{{ $eventAnalysis->description }}</p>

            <div class="event-analysis-metrics">
                <div class="event-analysis-metric">
                    <span class="event-analysis-metric-label">Home motivation</span>
                    <span class="event-analysis-metric-value">{{ $eventAnalysis->home_motivation }}/{{ \App\Models\EventAnalysis::STRENGTH_MAX }}</span>
                </div>
                <div class="event-analysis-metric">
                    <span class="event-analysis-metric-label">Away motivation</span>
                    <span class="event-analysis-metric-value">{{ $eventAnalysis->away_motivation }}/{{ \App\Models\EventAnalysis::STRENGTH_MAX }}</span>
                </div>
                <div class="event-analysis-metric">
                    <span class="event-analysis-metric-label">Home class</span>
                    <span class="event-analysis-metric-value">{{ $eventAnalysis->home_class }}/{{ \App\Models\EventAnalysis::STRENGTH_MAX }}</span>
                </div>
                <div class="event-analysis-metric">
                    <span class="event-analysis-metric-label">Away class</span>
                    <span class="event-analysis-metric-value">{{ $eventAnalysis->away_class }}/{{ \App\Models\EventAnalysis::STRENGTH_MAX }}</span>
                </div>
            </div>

            @if (! empty($eventAnalysis->influenced_by))
                <div class="event-analysis-influenced">
                    <h3 class="event-analysis-influenced-title">Can be influenced by</h3>
                    <ul class="event-analysis-influenced-list">
                        @foreach ($eventAnalysis->influenced_by as $index => $label)
                            @php
                                $influencerEventId = $eventAnalysis->influenced_by_event_ids[$index] ?? null;
                            @endphp
                            <li>
                                @if ($influencerEventId)
                                    <a href="{{ route('events.show', $influencerEventId) }}" class="event-analysis-influenced-link">
                                        {{ $label }}
                                    </a>
                                @else
                                    {{ $label }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </article>
    </section>
@endif
