{{--
    "Show N entries", export buttons and search box above a list table.
    Keeps the current sort and filters when the page size or search changes.
--}}
@props(['action', 'perPage', 'exports' => [], 'print' => false, 'allowAll' => false, 'copy' => false])

<form method="GET" action="{{ $action }}"
      class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3 text-13 print:hidden">
    @foreach (request()->except('per_page', 'search', 'page') as $name => $value)
        @if (is_scalar($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach

    <label class="flex items-center gap-2 text-ink-2">
        {{ __('Show') }}
        <select name="per_page" onchange="this.form.submit()" class="rounded-md border-line-strong bg-surface py-1 pe-8 text-13 shadow-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            @foreach (\App\Support\PageSize::OPTIONS as $size)
                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
            @endforeach
            @if ($allowAll)
                <option value="all" @selected($perPage === \App\Support\PageSize::ALL)>{{ __('All') }}</option>
            @endif
        </select>
        {{ __('entries') }}
    </label>

    <div class="flex flex-wrap items-center gap-2">
        @if ($copy)
            {{-- Copies this page's table as tab-separated text (pastes straight into Excel), without the Action column. --}}
            <button type="button" x-data="{ copied: false }"
                    x-on:click="
                        const table = $el.closest('.rounded-lg').querySelector('table');
                        const head = table.tHead.rows[table.tHead.rows.length - 1].cells;
                        const skip = [...head].map(c => c.innerText.trim() === 'Action');
                        const text = [...table.rows].filter(r => r.cells.length === skip.length)
                            .map(r => [...r.cells].filter((c, i) => ! skip[i]).map(c => c.innerText.replace(/\s+/g, ' ').trim()).join('\t'))
                            .join('\n');
                        navigator.clipboard.writeText(text).then(() => { copied = true; setTimeout(() => copied = false, 2000) });
                    "
                    class="inline-flex h-8 items-center rounded-md border border-line-strong bg-surface px-3 text-13 font-medium text-ink shadow-hairline hover:bg-sunken">
                <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</span>
            </button>
        @endif
        @foreach ($exports as $label => $url)
            <a href="{{ $url }}" class="inline-flex h-8 items-center rounded-md border border-line-strong bg-surface px-3 text-13 font-medium text-ink shadow-hairline hover:bg-sunken">{{ $label }}</a>
        @endforeach
        @if ($print)
            <button type="button" onclick="window.print()"
                    class="inline-flex h-8 items-center rounded-md border border-line-strong bg-surface px-3 text-13 font-medium text-ink shadow-hairline hover:bg-sunken">{{ __('Print') }}</button>
        @endif
        <label class="flex items-center gap-2 text-ink-2">
            {{ __('Search:') }}
            <span class="relative">
                <x-icon name="search" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                <x-text-input type="search" name="search" :value="request('search')" class="w-56 py-1 ps-8 text-13" />
            </span>
        </label>
    </div>
</form>
