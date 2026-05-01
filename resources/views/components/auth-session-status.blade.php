@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-[#8bffcd]']) }}>
        {{ $status }}
    </div>
@endif
