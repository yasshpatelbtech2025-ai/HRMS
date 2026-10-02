@props(['active' => false, 'icon' => null])

@php
$classes = $active
            ? 'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium bg-brand-600 text-white'
            : 'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    @if ($icon)
        <span class="shrink-0">{{ $icon }}</span>
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
