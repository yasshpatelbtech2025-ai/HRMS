{{-- Global search in the top bar (resources/js/global-search.js, SearchController). --}}
@php
    $results = <<<'HTML'
        <template x-if="loading && !groups.length">
            <div class="space-y-2 p-3">
                <div class="skeleton h-9"></div><div class="skeleton h-9"></div><div class="skeleton h-9 w-2/3"></div>
            </div>
        </template>
        <template x-if="!loading && query.trim().length >= 2 && !groups.length">
            <p class="px-4 py-6 text-center text-sm text-ink-3">No employees, companies or pages match “<span x-text="query.trim()"></span>”.</p>
        </template>
        <template x-for="group in groups" :key="group.label">
            <div class="py-1">
                <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-3" x-text="group.label"></p>
                <template x-for="item in group.items" :key="item.url">
                    <a :href="item.url" :data-search-index="indexOf(item)" x-on:mouseenter="active = indexOf(item)"
                       :class="active === indexOf(item) ? 'bg-primary-soft' : ''" class="mx-1 flex items-center gap-3 rounded-md px-2.5 py-2">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sunken text-xs font-semibold text-ink-2" x-text="item.initials ?? item.title.slice(0, 1)"></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm text-ink" x-text="item.title"></span>
                            <span class="block truncate text-xs text-ink-3" x-text="item.subtitle"></span>
                        </span>
                    </a>
                </template>
            </div>
        </template>
    HTML;
@endphp
<div x-data="globalSearch(@js(route('search')))" x-on:click.outside="open = false" x-on:keydown.escape.window="close()" class="print:hidden">
    {{-- Desktop: in the top bar. --}}
    <div class="relative hidden w-full max-w-md sm:block">
        <svg class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
        <input x-ref="input" type="search" x-model="query" x-on:input="search()" x-on:focus="open = query.trim().length >= 2"
               x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go()"
               placeholder="{{ __('Search employees, companies, pages…') }}" aria-label="{{ __('Search') }}" autocomplete="off"
               class="block h-9 w-full rounded-md border-line bg-surface/70 pe-12 ps-9 text-sm placeholder:text-ink-3 hover:border-line-strong focus:border-primary focus:bg-surface focus:ring-2 focus:ring-primary/20">
        <kbd class="pointer-events-none absolute end-2 top-1/2 hidden -translate-y-1/2 rounded border border-line bg-sunken px-1.5 font-mono text-[11px] text-ink-3 lg:block">/</kbd>
        <div x-show="open && query.trim().length >= 2" x-transition.opacity.duration.150ms style="display: none;"
             class="absolute inset-x-0 top-full z-50 mt-1.5 max-h-[70vh] overflow-y-auto rounded-lg border border-line bg-surface py-1 shadow-pop">
            {!! $results !!}
        </div>
    </div>

    {{-- Phones: a button that opens a full-width search. --}}
    <button type="button" x-on:click="focus()" class="rounded-md p-2 text-ink-2 hover:bg-sunken hover:text-ink sm:hidden" aria-label="{{ __('Search') }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
    </button>
    <div x-show="mobileOpen" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 bg-black/40 sm:hidden" x-on:click.self="close()">
        <div class="bg-surface p-3 shadow-pop">
            <div class="flex items-center gap-2">
                <input x-ref="mobileInput" type="search" x-model="query" x-on:input="search()"
                       x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go()"
                       placeholder="{{ __('Search employees, companies, pages…') }}" aria-label="{{ __('Search') }}" autocomplete="off"
                       class="block w-full rounded-md border-line-strong text-sm focus:border-primary focus:ring-2 focus:ring-primary/20">
                <button type="button" x-on:click="close()" class="px-2 text-sm font-medium text-ink-2">{{ __('Cancel') }}</button>
            </div>
            <div class="mt-2 max-h-[70vh] overflow-y-auto">{!! $results !!}</div>
        </div>
    </div>
</div>
