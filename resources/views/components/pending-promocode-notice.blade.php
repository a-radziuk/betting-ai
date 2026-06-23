@props([
    'action',
])

@php
    $pendingPromocode = \App\Support\PendingPromocodeSession::activePromocode();
@endphp

@if ($pendingPromocode)
    <div
        {{ $attributes->merge(['class' => 'rounded-lg border border-[#3b4e75] bg-[#13213b] px-4 py-3 text-sm text-[#dce7ff]']) }}
        role="status"
        aria-live="polite"
    >
        {{ __('A :days-day tips promocode is ready and will be applied after you :action.', [
            'days' => $pendingPromocode->days,
            'action' => $action,
        ]) }}
    </div>
@endif
