<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[#eaf0ff] leading-tight">
            {{ __('Dashboard') }}
        </h2>
        <p class="mt-1 text-sm text-[#9fb0d3] font-normal">{{ __('Your BetAI account overview.') }}</p>
    </x-slot>

    <div class="pb-8 space-y-4">
        @if (session('status'))
            <section class="card card-pad">
                <div class="flash">
                    {{ session('status') }}
                </div>
            </section>
        @endif

        <section class="card card-pad">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-[#c7d7fa] m-0">{{ __('Wallet') }}</h3>
            <div class="mt-3 flex flex-wrap items-baseline gap-3">
                <span class="text-3xl font-bold text-[#eaf0ff] tabular-nums">
                    {{ number_format((float) $wallet->balance, 2) }}
                </span>
                <span class="text-lg text-[#9fb0d3]">{{ $wallet->currency }}</span>
            </div>
            <p class="text-[#9fb0d3] text-sm mt-2 mb-0">
                {{ __('Available balance for betting.') }}
            </p>
        </section>

        <section class="card overflow-hidden">
            <div class="card-pad border-b border-[rgba(130,162,255,0.2)]">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-[#c7d7fa] m-0">{{ __('Your bets') }}</h3>
                <p class="text-[#9fb0d3] text-sm mt-1 mb-0">{{ __('Latest first.') }}</p>
            </div>
            @if ($bets->isEmpty())
                <p class="empty m-0">{{ __('No bets yet.') }}
                    <a href="{{ url('/') }}" class="text-[#5de2ff] hover:underline">{{ __('Browse events') }}</a>
                </p>
            @else
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('Placed') }}</th>
                                <th>{{ __('Event') }}</th>
                                <th>{{ __('Selection') }}</th>
                                <th class="text-right">{{ __('Stake') }}</th>
                                <th class="text-right">{{ __('Odds') }}</th>
                                <th class="text-right">{{ __('Potential') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bets as $bet)
                                <tr>
                                    <td class="whitespace-nowrap text-[#9fb0d3] text-sm">
                                        {{ $bet->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                    </td>
                                    <td>
                                        @if ($bet->event && $bet->event->homeTeam && $bet->event->awayTeam)
                                            <a href="{{ route('events.show', $bet->event) }}" class="text-[#5de2ff] hover:underline">
                                                {{ $bet->event->homeTeam->name }} — {{ $bet->event->awayTeam->name }}
                                            </a>
                                            @if ($bet->event->status !== \App\Models\Event::STATUS_SCHEDULED)
                                                <div class="text-xs text-[#9fb0d3] mt-1 tabular-nums">
                                                    {{ filled($bet->event->score) ? $bet->event->score : '—' }}
                                                </div>
                                            @endif
                                            <div class="text-xs text-[#9fb0d3] mt-1">
                                                {{ optional($bet->event->start_time)->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}
                                            </div>
                                        @else
                                            <span class="text-[#9fb0d3]">—</span>
                                        @endif
                                    </td>
                                    <td class="text-[#dce7ff]">
                                        <div>{{ $bet->odd?->selection?->name ?? '—' }}</div>
                                        <div class="text-xs text-[#9fb0d3] mt-1">
                                            {{ $bet->odd?->selection?->market?->type ?? '—' }}
                                        </div>
                                    </td>
                                    <td class="text-right tabular-nums text-[#eaf0ff]">
                                        {{ number_format((float) $bet->stake, 2) }}
                                    </td>
                                    <td class="text-right tabular-nums text-[#8bffcd]">
                                        {{ number_format((float) $bet->odds_at_bet, 2) }}
                                    </td>
                                    <td class="text-right tabular-nums text-[#eaf0ff]">
                                        {{ number_format((float) $bet->potential_return, 2) }}
                                    </td>
                                    <td>
                                        <span @class([
                                            'bet-status',
                                            'bet-status--pending' => $bet->status === \App\Models\UserBet::STATUS_PENDING,
                                            'bet-status--won' => $bet->status === \App\Models\UserBet::STATUS_WON,
                                            'bet-status--lost' => $bet->status === \App\Models\UserBet::STATUS_LOST,
                                            'bet-status--void' => $bet->status === \App\Models\UserBet::STATUS_VOID,
                                            'bet-status--cancelled' => $bet->status === \App\Models\UserBet::STATUS_CANCELLED,
                                        ])>{{ $bet->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($bets->hasPages())
                    <div class="dashboard-pagination card-pad border-t border-[rgba(130,162,255,0.2)]">
                        {{ $bets->links('vendor.pagination.tailwind') }}
                    </div>
                @endif
            @endif
        </section>

        <section class="card card-pad">
            <p class="text-[#eaf0ff] m-0">{{ __("You're logged in!") }}</p>
            <p class="text-[#9fb0d3] text-sm mt-2 mb-0">
                <a href="{{ url('/') }}" class="text-[#5de2ff] hover:underline">{{ __('Browse upcoming events') }}</a>
            </p>
        </section>
    </div>
</x-app-layout>
