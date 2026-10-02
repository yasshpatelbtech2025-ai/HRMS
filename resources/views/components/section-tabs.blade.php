@props(['section'])

@php
    $tabs = collect(config("navigation.{$section}", []))
        ->mapWithKeys(fn ($label, $key) => [$section.'.'.($key === '' ? 'index' : $key) => $label]);

    // A tab is active on its own route and the routes beneath it (e.g.
    // employees.departments.*); anything else in the section belongs to the
    // landing tab (e.g. employees.create, employees.edit).
    $activeTab = $tabs->keys()->first(fn ($name) => request()->routeIs($name, "{$name}.*"))
        ?? $section.'.index';
@endphp

@if ($tabs->count() > 1)
    <nav class="mb-6 -mx-1 overflow-x-auto print:hidden" aria-label="{{ __('Section') }}">
        <ul class="flex w-max gap-1 rounded-lg bg-white p-1 shadow-sm ring-1 ring-gray-200">
            @foreach ($tabs as $name => $label)
                @php($active = $name === $activeTab)
                <li>
                    <a href="{{ route($name) }}" @if ($active) aria-current="page" @endif
                       class="block whitespace-nowrap rounded-md px-4 py-2 text-sm font-medium transition {{ $active ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        {{ __($label) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
