{{--
    The tab bar for a sidebar section (config/navigation.php), or with
    `nested`, the tabs inside one of its tabs (config/subnavigation.php).
--}}
@props(['section', 'nested' => false])

@php
    $tabs = collect($nested ? config('subnavigation')[$section] ?? [] : config("navigation.{$section}", []))
        ->mapWithKeys(fn ($label, $key) => [($key === '' ? ($nested ? $section : "{$section}.index") : "{$section}.{$key}") => $label])
        // Admin-only tabs are not shown to users (config/access.php).
        ->filter(fn ($label, $name) => \App\Support\CompanyAccess::canOpen($name));

    // A tab is active on its own route and the routes beneath it (e.g.
    // employees.departments.*); anything else in the section belongs to the
    // landing tab (e.g. employees.create, employees.edit).
    $landing = $nested ? $section : "{$section}.index";
    $activeTab = $tabs->keys()->reject(fn ($name) => $name === $landing)->first(fn ($name) => request()->routeIs($name, "{$name}.*"))
        ?? $landing;
@endphp

@if ($tabs->count() > 1)
    <nav class="{{ $nested ? 'mb-4' : 'mb-6' }} -mx-1 overflow-x-auto print:hidden" aria-label="{{ $nested ? __('Sub-section') : __('Section') }}">
        <ul class="flex w-max gap-1 rounded-lg p-1 {{ $nested ? 'bg-gray-100' : 'bg-white shadow-sm ring-1 ring-gray-200' }}">
            @foreach ($tabs as $name => $label)
                @php $active = $name === $activeTab; @endphp
                <li>
                    <a href="{{ route($name) }}" @if ($active) aria-current="page" @endif
                       class="block whitespace-nowrap rounded-md font-medium transition {{ $nested ? 'px-2.5 py-[5px] text-[15px]' : 'px-2.5 py-[5px] text-[15px]' }}
                              {{ $active ? ($nested ? 'bg-white text-gray-900 shadow-sm' : 'bg-gray-900 text-white') : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                        {{ __($label) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
