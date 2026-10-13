@props(['title' => null, 'description' => null, 'padded' => true])

<section {{ $attributes->class(['card']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-3.5">
            <div class="min-w-0">
                @if ($title)<h2 class="text-sm font-semibold text-ink">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-13 text-ink-3">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['p-5' => $padded])>{{ $slot }}</div>
    @isset($footer)
        <footer class="flex items-center justify-end gap-2 border-t border-line bg-sunken/50 px-5 py-3">{{ $footer }}</footer>
    @endisset
</section>
