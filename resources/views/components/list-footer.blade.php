@props(['paginator'])

<div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-5 py-4 text-sm text-gray-600 print:hidden">
    <p>
        @if ($paginator->total())
            {{ __('Showing :from to :to of :total entries', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        @else
            {{ __('Showing 0 entries') }}
        @endif
    </p>
    {{ $paginator->onEachSide(1)->links() }}
</div>
