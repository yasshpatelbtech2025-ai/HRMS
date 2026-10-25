{{--
    A button or, with `href`, a link that looks like one.
    variant: primary (one per view: the main thing to do) | secondary | ghost | danger | danger-ghost
    size: sm (32px) | md (38px). `square` for an icon-only button (give it an aria-label).
--}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null, 'iconRight' => null, 'square' => false])
@php
    $variants = [
        'primary' => 'bg-primary text-on-primary hover:bg-primary-hover',
        'secondary' => 'border border-line-strong bg-surface text-ink hover:border-field hover:bg-sunken',
        'ghost' => 'text-ink-2 hover:bg-sunken hover:text-ink',
        'danger' => 'bg-danger text-on-primary hover:bg-danger-hover',
        'danger-ghost' => 'text-danger hover:bg-danger-soft',
    ];
    $sizes = $square
        ? ['sm' => 'size-control-sm', 'md' => 'size-control']
        : ['sm' => 'h-control-sm px-3 text-13', 'md' => 'h-control px-4 text-sm'];
    $classes = 'relative inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-control font-medium select-none focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-50 aria-busy:pointer-events-none '
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
        <span class="inline-flex items-center gap-2 group-aria-busy:invisible">
            @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
            {{ $slot }}
            @if ($iconRight)<x-icon :name="$iconRight" class="size-4" />@endif
        </span>
    </button>
@endif
