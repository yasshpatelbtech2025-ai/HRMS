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
    <a href="{{ $url }}" class="inline-flex items-center gap-1 hover:text-gray-900">
        {{ $slot }} <span class="text-xs">{{ $active ? ($direction === 'asc' ? '▲' : '▼') : '' }}</span>
    </a>
</th>
