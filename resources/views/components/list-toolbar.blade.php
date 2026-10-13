{{--
    The tools above a list table: search on the left; export, saved views, column
    visibility, row density and page size on the right. Keeps the current sort and
    filters when the page size or search changes. Views, columns and density are
    remembered per list in this browser (resources/js/ui/table-tools.js).
--}}
@props(['action', 'perPage', 'exports' => [], 'print' => false, 'allowAll' => false, 'copy' => false])

@php
    $toolId = str_replace('.', '-', request()->route()?->getName() ?? 'list');
    $tool = 'inline-flex h-8 items-center gap-1.5 rounded-md px-2.5 text-13 font-medium text-ink-2 hover:bg-sunken hover:text-ink';
    $menuItem = 'flex w-full items-center gap-2 rounded-[5px] px-2.5 py-1.5 text-start text-sm text-ink hover:bg-sunken';
@endphp

<div x-data="tableTools({ id: @js($toolId) })" class="flex flex-wrap items-center gap-2 border-b border-line px-3 py-2.5 text-13 print:hidden">
    <style x-text="css"></style>

    <form method="GET" action="{{ $action }}" class="contents" x-ref="form">
        @foreach (request()->except('per_page', 'search', 'page') as $name => $value)
            @if (is_scalar($value))
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="relative w-full sm:w-72" role="search">
            <x-icon name="search" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search this list…') }}" aria-label="{{ __('Search:') }}"
                   class="block h-8 w-full rounded-md border-line-strong bg-surface py-1 pe-3 ps-8 text-13 placeholder:text-ink-3 focus:border-primary focus:ring-2 focus:ring-primary/20">
        </div>
        @if (request('search'))
            <a href="{{ request()->fullUrlWithoutQuery(['search', 'page']) }}" class="text-13 text-ink-3 hover:text-ink">{{ __('Clear search') }}</a>
        @endif

        <span class="flex-1"></span>

        <div class="flex flex-wrap items-center gap-0.5">
            {{-- Export / copy / print --}}
            @if ($copy || $exports || $print)
                <x-ui.menu width="w-44">
                    <x-slot:trigger>
                        <button type="button" class="{{ $tool }}" aria-haspopup="menu"><x-icon name="download" class="size-4" /><span class="max-sm:sr-only">{{ __('Export') }}</span><x-icon name="chevron-down" class="size-3" /></button>
                    </x-slot:trigger>
                    @if ($copy)
                        {{-- Copies this page's table as tab-separated text (pastes straight into Excel), without the Action column. --}}
                        <button type="button" role="menuitem" x-data="{ copied: false }" class="{{ $menuItem }}"
                                x-on:click="
                                    const table = $el.closest('.rounded-lg').querySelector('table');
                                    const head = table.tHead.rows[table.tHead.rows.length - 1].cells;
                                    const skip = [...head].map(c => c.innerText.trim() === 'Action');
                                    const text = [...table.rows].filter(r => r.cells.length === skip.length)
                                        .map(r => [...r.cells].filter((c, i) => ! skip[i]).map(c => c.innerText.replace(/\s+/g, ' ').trim()).join('\t'))
                                        .join('\n');
                                    navigator.clipboard.writeText(text).then(() => { copied = true; setTimeout(() => copied = false, 2000) });
                                ">
                            <x-icon name="document" class="size-4 text-ink-3" /><span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</span>
                        </button>
                    @endif
                    @foreach ($exports as $label => $url)
                        <a href="{{ $url }}" role="menuitem" class="{{ $menuItem }}"><x-icon name="download" class="size-4 text-ink-3" />{{ $label }}</a>
                    @endforeach
                    @if ($print)
                        <button type="button" role="menuitem" onclick="window.print()" class="{{ $menuItem }}"><x-icon name="document" class="size-4 text-ink-3" />{{ __('Print') }}</button>
                    @endif
                </x-ui.menu>
            @endif

            {{-- Saved views --}}
            <x-ui.menu width="w-64">
                <x-slot:trigger>
                    <button type="button" class="{{ $tool }}" aria-haspopup="menu"><x-icon name="bookmark" class="size-4" /><span class="max-sm:sr-only">{{ __('Views') }}</span></button>
                </x-slot:trigger>
                <p class="px-2.5 pb-1 pt-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-3">{{ __('Saved views') }}</p>
                <template x-for="(view, i) in views" :key="view.name">
                    <div class="group flex items-center rounded-[5px] hover:bg-sunken">
                        <a :href="view.url" role="menuitem" class="flex-1 truncate px-2.5 py-1.5 text-sm text-ink" x-text="view.name"></a>
                        <button type="button" @click="removeView(i)" class="me-1 rounded p-1 text-ink-3 opacity-0 hover:text-danger focus:opacity-100 group-hover:opacity-100" :aria-label="@js(__('Delete view')) + ' ' + view.name"><x-icon name="x" class="size-3.5" /></button>
                    </div>
                </template>
                <p x-show="views.length === 0" class="px-2.5 py-1.5 text-13 text-ink-3">{{ __('Filter or search the list, then save it here to come back in one click.') }}</p>
                <div class="mt-1 flex gap-1 border-t border-line p-1.5 pt-2">
                    <input x-model="newView" @keydown.enter.prevent="saveView()" type="text" placeholder="{{ __('Name this view') }}" aria-label="{{ __('View name') }}"
                           class="h-8 w-full rounded-md border-line-strong py-1 text-13 focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <button type="button" @click="saveView()" :disabled="!newView.trim()" class="h-8 rounded-md border border-line-strong bg-surface px-2.5 text-13 font-medium text-ink hover:bg-sunken disabled:opacity-50">{{ __('Save') }}</button>
                </div>
            </x-ui.menu>

            {{-- Columns --}}
            <x-ui.menu width="w-56">
                <x-slot:trigger>
                    <button type="button" class="{{ $tool }}" aria-haspopup="menu" x-show="columns.length"><x-icon name="columns" class="size-4" /><span class="max-sm:sr-only">{{ __('Columns') }}</span></button>
                </x-slot:trigger>
                <p class="px-2.5 pb-1 pt-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-3">{{ __('Show columns') }}</p>
                <div class="max-h-72 overflow-y-auto">
                    <template x-for="col in columns" :key="col.index">
                        <label class="flex cursor-pointer items-center gap-2 rounded-[5px] px-2.5 py-1.5 text-sm text-ink hover:bg-sunken">
                            <input type="checkbox" class="size-4 rounded border-line-strong text-primary focus:ring-primary/30" :checked="!hidden.includes(col.index)" @change="toggle(col.index)">
                            <span x-text="col.label"></span>
                        </label>
                    </template>
                </div>
            </x-ui.menu>

            {{-- Density --}}
            <button type="button" class="{{ $tool }} w-8 justify-center px-0" @click="density = density === 'compact' ? 'comfortable' : 'compact'"
                    :title="density === 'compact' ? @js(__('Comfortable rows')) : @js(__('Compact rows'))" :aria-label="density === 'compact' ? @js(__('Comfortable rows')) : @js(__('Compact rows'))">
                <x-icon name="density" class="size-4" />
            </button>

            <label class="ms-1 flex items-center gap-1.5 text-ink-3">
                {{ __('Show') }}
                <select name="per_page" onchange="this.form.submit()" class="h-8 rounded-md border-line-strong bg-surface py-0 pe-7 ps-2 text-13 text-ink focus:border-primary focus:ring-2 focus:ring-primary/20">
                    @foreach (\App\Support\PageSize::OPTIONS as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                    @endforeach
                    @if ($allowAll)
                        <option value="all" @selected($perPage === \App\Support\PageSize::ALL)>{{ __('All') }}</option>
                    @endif
                </select>
                <span class="max-sm:hidden">{{ __('entries') }}</span>
            </label>
        </div>
    </form>
</div>
