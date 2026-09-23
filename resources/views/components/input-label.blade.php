@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-base font-semibold text-white']) }}>
    {{ $value ?? $slot }}
</label>
