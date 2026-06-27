@php
    $logoVariant = match (config('app.logo')) {
        'B' => 'b',
        default => 'a',
    };
@endphp

@include('layouts.partials.logos.'.$logoVariant)

@if (config('app.is_beta'))
    <span class="logo-beta">{{ __('BETA') }}</span>
@endif
