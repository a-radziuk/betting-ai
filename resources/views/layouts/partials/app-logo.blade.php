@php
    $logoVariant = match (config('app.logo')) {
        'B' => 'b',
        default => 'a',
    };
@endphp

@include('layouts.partials.logos.'.$logoVariant)
