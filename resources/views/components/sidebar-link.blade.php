@props(['active' => false, 'icon' => null])

@php
// Sized as in the old HRMS: 16px semibold items with roomy padding.
$classes = $active
            ? 'flex items-center gap-4 px-2.5 py-[15px] rounded-[5px] text-base font-semibold bg-gray-800 text-white'
            : 'flex items-center gap-4 px-2.5 py-[15px] rounded-[5px] text-base font-semibold text-gray-400 hover:bg-gray-800 hover:text-white transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    @if ($icon)
        <span class="shrink-0">{{ $icon }}</span>
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
