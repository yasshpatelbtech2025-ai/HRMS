@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-13 font-medium text-ink-2']) }}>
    {{ $value ?? $slot }}
</label>
