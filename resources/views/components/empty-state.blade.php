{{--
    An empty list: an icon, a message and, optionally, what to do next.
    As a table row, pass `colspan`. Slot: the message; `action`: a button or link.
--}}
@props(['colspan' => null, 'title' => null, 'icon' => 'inbox'])

@php
    $icons = [
        'inbox' => 'M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z',
        'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'building' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21',
        'search' => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
        'check' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    ];
@endphp

@if ($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="px-5 py-12">
            {{-- Kept in view when the table is wider than its scroll box (phones). --}}
            <div class="sticky start-5" style="width: min(100%, calc(100vw - 5rem));">
@endif
<div {{ $attributes->merge(['class' => 'mx-auto flex max-w-md flex-col items-center text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-8 ring-brand-50/50">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] ?? $icons['inbox'] }}" /></svg>
    </span>
    @if ($title)
        <p class="mt-4 text-base font-semibold text-gray-900">{{ $title }}</p>
    @endif
    <div class="{{ $title ? 'mt-1' : 'mt-4' }} text-sm text-gray-500">{{ $slot }}</div>
    @isset ($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
@if ($colspan)
            </div>
        </td>
    </tr>
@endif
