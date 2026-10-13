{{-- Breadcrumbs (from config/navigation.php) + page title + primary action slot. --}}
@props(['title', 'description' => null, 'crumbs' => null])
@php
    $crumbs ??= app(\App\Support\Navigation::class)->breadcrumbs();
    if (($crumbs[array_key_last($crumbs) ?? 0]['label'] ?? null) === $title) {
        array_pop($crumbs);
    }
@endphp

<div {{ $attributes->class(['mb-5 flex flex-wrap items-end justify-between gap-x-4 gap-y-3']) }}>
    <div class="min-w-0">
        @if ($crumbs)
            <nav aria-label="{{ __('Breadcrumb') }}" class="mb-1">
                <ol class="flex flex-wrap items-center gap-1 text-13 text-ink-3">
                    @foreach ($crumbs as $crumb)
                        <li class="flex items-center gap-1">
                            <a href="{{ $crumb['url'] }}" class="rounded hover:text-ink hover:underline">{{ $crumb['label'] }}</a>
                            <x-icon name="chevron-right" class="size-3" />
                        </li>
                    @endforeach
                    <li aria-current="page" class="text-ink-2">{{ $title }}</li>
                </ol>
            </nav>
        @endif
        <h1 class="truncate text-xl font-semibold text-ink">{{ $title }}</h1>
        @if ($description)<p class="mt-1 text-sm text-ink-3">{{ $description }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
