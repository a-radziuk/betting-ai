@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-[#c7d7fa]']) }}>
    {{ $value ?? $slot }}
</label>
