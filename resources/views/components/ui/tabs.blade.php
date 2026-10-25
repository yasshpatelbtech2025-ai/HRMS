{{--
    Tabs that link to pages: items = [['label' => .., 'href' => .., 'active' => bool, 'count' => ?int]].
    An underline marks the current tab; scrolls sideways on phones instead of wrapping.
--}}
@props(['items', 'label' => __('Sections')])
<nav aria-label="{{ $label }}" {{ $attributes->class(['-mx-1 mb-5 overflow-x-auto print:hidden']) }}>
    <ul class="flex w-max min-w-full gap-1 border-b border-line px-1">
        @foreach ($items as $item)
            <li>
                <a href="{{ $item['href'] }}" @if ($item['active'] ?? false) aria-current="page" @endif
                   class="relative -mb-px flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm transition-colors {{ ($item['active'] ?? false) ? 'border-primary font-medium text-ink' : 'border-transparent text-ink-2 hover:border-line-strong hover:text-ink' }}">
                    {{ $item['label'] }}
                    @isset($item['count'])<span class="rounded-full bg-sunken px-1.5 text-xs font-medium tabular text-ink-2">{{ $item['count'] }}</span>@endisset
                </a>
            </li>
        @endforeach
    </ul>
</nav>
