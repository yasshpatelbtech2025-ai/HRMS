@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination') }}" class="flex items-center gap-1">
        @php $link = 'inline-flex h-8 min-w-8 items-center justify-center rounded-control px-2 text-13 tabular'; @endphp
        @if ($paginator->onFirstPage())
            <span class="{{ $link }} text-ink-3/50" aria-disabled="true"><x-icon name="chevron-left" class="size-4" /><span class="sr-only">{{ __('Previous') }}</span></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }} text-ink-2 hover:bg-sunken"><x-icon name="chevron-left" class="size-4" /><span class="sr-only">{{ __('Previous') }}</span></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="{{ $link }} text-ink-3 max-sm:hidden">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="{{ $link }} bg-primary-soft font-semibold text-primary-ink">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $link }} text-ink-2 hover:bg-sunken max-sm:hidden">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }} text-ink-2 hover:bg-sunken"><x-icon name="chevron-right" class="size-4" /><span class="sr-only">{{ __('Next') }}</span></a>
        @else
            <span class="{{ $link }} text-ink-3/50" aria-disabled="true"><x-icon name="chevron-right" class="size-4" /><span class="sr-only">{{ __('Next') }}</span></span>
        @endif
    </nav>
@endif
