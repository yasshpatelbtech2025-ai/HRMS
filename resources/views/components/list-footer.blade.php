@props(['paginator'])

<div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-2.5 text-13 text-ink-3 print:hidden">
    <p>
        @if ($paginator->total())
            {{ __('Showing :from to :to of :total entries', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        @else
            {{ __('Showing 0 entries') }}
        @endif
    </p>
    {{ $paginator->onEachSide(1)->links('components.ui.pagination') }}
</div>
