{{-- Global search in the top bar (resources/js/global-search.js, SearchController). --}}
@php
    $results = <<<'HTML'
        <template x-if="loading && !groups.length">
            <div class="space-y-2 p-3">
                <div class="skeleton h-9"></div><div class="skeleton h-9"></div><div class="skeleton h-9 w-2/3"></div>
            </div>
        </template>
        <template x-if="!loading && query.trim().length >= 2 && !groups.length">
            <p class="px-4 py-6 text-center text-sm text-gray-500">No employees, companies or pages match “<span x-text="query.trim()"></span>”.</p>
        </template>
        <template x-for="group in groups" :key="group.label">
            <div class="py-1">
                <p class="px-4 pb-1 pt-2 text-xs font-semibold uppercase tracking-wide text-gray-400" x-text="group.label"></p>
                <template x-for="item in group.items" :key="item.url">
                    <a :href="item.url" :data-search-index="indexOf(item)" x-on:mouseenter="active = indexOf(item)"
                       :class="active === indexOf(item) ? 'bg-brand-50' : ''" class="flex items-center gap-3 px-4 py-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700" x-text="item.initials ?? item.title.slice(0, 1)"></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-gray-900" x-text="item.title"></span>
                            <span class="block truncate text-xs text-gray-500" x-text="item.subtitle"></span>
                        </span>
                    </a>
                </template>
            </div>
        </template>
    HTML;
@endphp
<div x-data="globalSearch(@js(route('search')))" x-on:click.outside="open = false" x-on:keydown.escape.window="close()" class="print:hidden">
    {{-- Desktop: in the top bar. --}}
    <div class="relative hidden w-72 sm:block lg:w-96">
        <svg class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
        <input x-ref="input" type="search" x-model="query" x-on:input="search()" x-on:focus="open = query.trim().length >= 2"
               x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go()"
               placeholder="{{ __('Search employees, companies, pages…') }}" aria-label="{{ __('Search') }}" autocomplete="off"
               class="block w-full rounded-md border-gray-200 bg-gray-50 py-2 pe-12 ps-9 text-sm placeholder:text-gray-400 focus:border-brand-500 focus:bg-white focus:ring-brand-500">
        <kbd class="pointer-events-none absolute end-2 top-1/2 hidden -translate-y-1/2 rounded border border-gray-200 bg-white px-1.5 text-[11px] font-medium text-gray-400 lg:block">/</kbd>
        <div x-show="open && query.trim().length >= 2" x-transition.opacity.duration.150ms style="display: none;"
             class="absolute inset-x-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-lg bg-white py-1 shadow-lg ring-1 ring-gray-200">
            {!! $results !!}
        </div>
    </div>

    {{-- Phones: a button that opens a full-width search. --}}
    <button type="button" x-on:click="focus()" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 sm:hidden" aria-label="{{ __('Search') }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
    </button>
    <div x-show="mobileOpen" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 bg-gray-900/40 sm:hidden" x-on:click.self="close()">
        <div class="bg-white p-3 shadow-lg">
            <div class="flex items-center gap-2">
                <input x-ref="mobileInput" type="search" x-model="query" x-on:input="search()"
                       x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go()"
                       placeholder="{{ __('Search employees, companies, pages…') }}" aria-label="{{ __('Search') }}" autocomplete="off"
                       class="block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500">
                <button type="button" x-on:click="close()" class="px-2 text-sm font-medium text-gray-600">{{ __('Cancel') }}</button>
            </div>
            <div class="mt-2 max-h-[70vh] overflow-y-auto">{!! $results !!}</div>
        </div>
    </div>
</div>
