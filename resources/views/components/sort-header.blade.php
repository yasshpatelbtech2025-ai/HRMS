{{-- A column heading that sorts the list by $column, toggling direction on repeat clicks. --}}
@props(['column', 'default' => false])

@php
    $current = request('sort', $default ? $column : null);
    $direction = request('direction') === 'desc' ? 'desc' : 'asc';
    $active = $current === $column;
    $url = request()->fullUrlWithQuery([
        'sort' => $column,
        'direction' => $active && $direction === 'asc' ? 'desc' : 'asc',
        'page' => null,
    ]);
@endphp

<th {{ $attributes->merge(['class' => 'px-5 py-3']) }} @if ($active) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ $url }}" class="group inline-flex items-center gap-1 rounded hover:text-ink {{ $active ? 'text-ink' : '' }}">
        {{ $slot }}
        @if ($active)
            <x-icon :name="$direction === 'asc' ? 'arrow-up' : 'arrow-down'" class="size-3" stroke="2" />
        @else
            <x-icon name="sort" class="size-3 opacity-0 transition group-hover:opacity-60" />
        @endif
    </a>
</th>
