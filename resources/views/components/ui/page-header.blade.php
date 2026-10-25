{{--
    The page title on the ruled margin (.page-head): optional breadcrumbs, the title,
    one line of description, and `actions` on the right: one primary action, the
    rest secondary or in a menu.
--}}
@props(['title', 'description' => null, 'crumbs' => null])
@php
    $crumbs ??= [];
    if (($crumbs[array_key_last($crumbs) ?? 0]['label'] ?? null) === $title) {
        array_pop($crumbs);
    }
@endphp

<div {{ $attributes->class(['page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3']) }}>
    <div class="min-w-0">
        @if ($crumbs)
            <nav aria-label="{{ __('Breadcrumb') }}" class="mb-1.5 print:hidden">
                <ol class="flex flex-wrap items-center gap-1 text-13 text-ink-3">
                    @foreach ($crumbs as $crumb)
                        <li class="flex items-center gap-1">
                            <a href="{{ $crumb['url'] }}" class="rounded hover:text-ink hover:underline">{{ $crumb['label'] }}</a>
                            <x-icon name="chevron-right" class="size-3" />
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="text-2xl font-semibold text-ink">{{ $title }}</h1>
        @if ($description)<p class="mt-1 max-w-3xl text-sm text-ink-2">{{ $description }}</p>@endif
        {{ $meta ?? '' }}
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 print:hidden">{{ $actions }}</div>
    @endisset
</div>
