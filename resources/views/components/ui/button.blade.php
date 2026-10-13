@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null, 'iconRight' => null, 'square' => false])
@php
    $variants = [
        'primary' => 'bg-primary text-white shadow-hairline hover:bg-primary-hover',
        'secondary' => 'border border-line-strong bg-surface text-ink shadow-hairline hover:bg-sunken',
        'ghost' => 'text-ink-2 hover:bg-sunken hover:text-ink',
        'danger' => 'bg-danger text-white shadow-hairline hover:bg-danger-hover',
        'danger-ghost' => 'text-danger hover:bg-danger-soft',
    ];
    $sizes = $square
        ? ['sm' => 'size-8', 'md' => 'size-9']
        : ['sm' => 'h-8 px-2.5 text-13', 'md' => 'h-9 px-3.5 text-sm'];
    $classes = 'relative inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-control font-medium transition-colors select-none disabled:pointer-events-none disabled:opacity-50 aria-busy:pointer-events-none '
        .$variants[$variant].' '.$sizes[$size];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
        @if ($iconRight)<x-icon :name="$iconRight" class="size-4" />@endif
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes.' group']) }}>
        {{-- Spinner shows while the form is submitting (see app.js) --}}
        <span class="absolute inset-0 hidden items-center justify-center group-aria-busy:flex" aria-hidden="true">
            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </span>
        <span class="inline-flex items-center gap-1.5 group-aria-busy:invisible">
            @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
            {{ $slot }}
            @if ($iconRight)<x-icon :name="$iconRight" class="size-4" />@endif
        </span>
    </button>
@endif
