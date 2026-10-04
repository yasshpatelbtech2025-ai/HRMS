@props(['active' => false, 'icon' => null])

@php
// Sized as in the old HRMS: 16px semibold items with roomy padding.
$classes = $active
            ? 'flex items-center gap-4 px-2.5 py-[15px] rounded-md text-base font-semibold bg-white/10 text-white shadow-sm ring-1 ring-white/10'
            : 'flex items-center gap-4 px-2.5 py-[15px] rounded-md text-base font-semibold text-brand-100/70 hover:bg-white/5 hover:text-white transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>
    @if ($icon)
        <span class="shrink-0">{{ $icon }}</span>
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
