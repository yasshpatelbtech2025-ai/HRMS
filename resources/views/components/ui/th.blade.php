{{-- Header cell. `sort` makes it sortable via ?sort=&dir= ; `col` ties it to the column menu. --}}
@props(['sort' => null, 'col' => null, 'align' => 'start'])
@php
    $current = request('sort') === $sort;
    $dir = $current && request('dir') !== 'desc' ? 'desc' : 'asc';
    $url = $sort ? request()->fullUrlWithQuery(['sort' => $sort, 'dir' => $dir, 'page' => null]) : null;
@endphp
<th scope="col" @if ($col) data-col="{{ $col }}" @endif
    @if ($current) aria-sort="{{ request('dir') === 'desc' ? 'descending' : 'ascending' }}" @endif
    {{ $attributes->class(['whitespace-nowrap border-b border-line px-3 py-2.5 font-medium', 'text-end' => $align === 'end', 'text-start' => $align !== 'end']) }}>
    @if ($sort)
        <a href="{{ $url }}" class="group inline-flex items-center gap-1 rounded hover:text-ink {{ $current ? 'text-ink' : '' }}">
            {{ $slot }}
            @if ($current)
                <x-icon :name="request('dir') === 'desc' ? 'arrow-down' : 'arrow-up'" class="size-3" stroke="2" />
            @else
                <x-icon name="sort" class="size-3 opacity-0 transition group-hover:opacity-60" />
            @endif
        </a>
    @else
        {{ $slot }}
    @endif
</th>
