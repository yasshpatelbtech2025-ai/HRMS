{{-- The SRYMPEX wordmark: a red "S" mark and the name. `dark` for use on a black background. --}}
@props(['dark' => false, 'size' => 'md'])

@php
    [$mark, $text] = match ($size) {
        'lg' => ['h-12 w-12 text-2xl rounded-lg', 'text-3xl'],
        default => ['h-8 w-8 text-lg rounded-md', 'text-xl'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="flex {{ $mark }} items-center justify-center bg-brand-600 font-black leading-none text-white shadow-sm" aria-hidden="true">S</span>
    <span class="{{ $text }} font-extrabold tracking-[0.12em] {{ $dark ? 'text-white' : 'text-gray-950' }}">SRYMPEX</span>
</span>
