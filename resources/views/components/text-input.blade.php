@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'control px-3']) }}>
