<div {{ $attributes->merge(['class' => 'app-logo app-logo--'.strtolower(config('app.logo'))]) }}>
    @include('layouts.partials.app-logo')
</div>
