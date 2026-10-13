{{--
    List page table: toolbar (filters, saved views, columns, density), sticky header,
    bulk select + action bar, empty state, pagination.

    id:         storage key for this table's per-browser preferences
    columns:    ['key' => 'Label'] that can be hidden (others are always shown)
    paginator:  the LengthAwarePaginator being listed
    bulkDelete: URL for DELETE with ids[] (enables row checkboxes)
    noun:       singular/plural for messages, e.g. ['employee', 'employees']
    Slots: filters, head (th cells), default (tr rows), empty
--}}
@props(['id', 'columns' => [], 'paginator', 'bulkDelete' => null, 'noun' => ['item', 'items']])

<div x-data="dataTable({ id: @js($id), noun: @js($noun) })" data-table="{{ $id }}" :data-density="density" {{ $attributes->class(['card overflow-hidden']) }}>
    {{-- Hidden-column rules are generated so hidden cells never render --}}
    <style x-text="hiddenCss"></style>

    <div class="flex flex-wrap items-center gap-2 border-b border-line px-3 py-2.5">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">{{ $filters ?? '' }}</div>

        <div class="flex items-center gap-1">
            {{-- Saved views --}}
            <x-ui.menu width="w-64">
                <x-slot:trigger>
                    <x-ui.button variant="ghost" size="sm" type="button" icon="bookmark" aria-label="{{ __('Saved views') }}"><span class="max-sm:hidden">{{ __('Views') }}</span></x-ui.button>
                </x-slot:trigger>
                <p class="px-2.5 pb-1 pt-1.5 text-xs font-medium uppercase tracking-wide text-ink-3">{{ __('Saved views') }}</p>
                <template x-for="(view, i) in views" :key="view.name">
                    <div class="group flex items-center rounded-[5px] hover:bg-sunken">
                        <a :href="view.url" role="menuitem" class="flex-1 truncate px-2.5 py-1.5 text-sm text-ink" x-text="view.name"></a>
                        <button type="button" @click="removeView(i)" class="me-1 rounded p-1 text-ink-3 opacity-0 hover:text-danger focus:opacity-100 group-hover:opacity-100" :aria-label="'{{ __('Delete view') }} ' + view.name"><x-icon name="x" class="size-3.5" /></button>
                    </div>
                </template>
                <p x-show="views.length === 0" class="px-2.5 py-1.5 text-13 text-ink-3">{{ __('Filter the list, then save it here to come back in one click.') }}</p>
                <form @submit.prevent="saveView()" class="mt-1 flex gap-1 border-t border-line p-1.5 pt-2">
                    <input x-model="newView" type="text" placeholder="{{ __('Name this view') }}" class="control h-8 py-1 text-13" aria-label="{{ __('View name') }}">
                    <x-ui.button size="sm" variant="secondary" x-bind:disabled="!newView.trim()">{{ __('Save') }}</x-ui.button>
                </form>
            </x-ui.menu>

            @if ($columns)
                <x-ui.menu width="w-56">
                    <x-slot:trigger>
                        <x-ui.button variant="ghost" size="sm" type="button" icon="columns" aria-label="{{ __('Show or hide columns') }}"><span class="max-sm:hidden">{{ __('Columns') }}</span></x-ui.button>
                    </x-slot:trigger>
                    <p class="px-2.5 pb-1 pt-1.5 text-xs font-medium uppercase tracking-wide text-ink-3">{{ __('Show columns') }}</p>
                    @foreach ($columns as $key => $label)
                        <label class="flex cursor-pointer items-center gap-2 rounded-[5px] px-2.5 py-1.5 text-sm text-ink hover:bg-sunken">
                            <x-ui.checkbox ::checked="!hidden.includes('{{ $key }}')" @change="toggleColumn('{{ $key }}')" />{{ $label }}
                        </label>
                    @endforeach
                </x-ui.menu>
            @endif

            <x-ui.button variant="ghost" size="sm" square type="button" @click="density = density === 'compact' ? 'comfortable' : 'compact'"
                         x-bind:aria-label="density === 'compact' ? '{{ __('Comfortable rows') }}' : '{{ __('Compact rows') }}'"
                         x-bind:title="density === 'compact' ? '{{ __('Comfortable rows') }}' : '{{ __('Compact rows') }}'">
                <x-icon name="density" class="size-4" />
            </x-ui.button>
        </div>
    </div>

    @isset($chips)
        <div class="flex flex-wrap items-center gap-1.5 border-b border-line px-3 py-2">{{ $chips }}</div>
    @endisset

    @if ($paginator->isEmpty())
        {{ $empty }}
    @else
        <div class="relative overflow-x-auto lg:max-h-[calc(100dvh-15rem)] lg:min-h-40 lg:overflow-auto" x-ref="scroller">
            <table class="w-full border-separate border-spacing-0 text-13">
                <thead class="sticky top-0 z-10 bg-sunken text-start text-xs font-medium text-ink-2">
                    <tr>
                        @if ($bulkDelete)
                            <th scope="col" class="w-10 border-b border-line px-3 py-2.5">
                                <x-ui.checkbox x-ref="all" @change="toggleAll($event.target.checked)" aria-label="{{ __('Select all on this page') }}" />
                            </th>
                        @endif
                        {{ $head }}
                    </tr>
                </thead>
                <tbody class="[&>tr:hover]:bg-sunken/60 [&>tr>td]:border-b [&>tr>td]:border-line [&>tr:last-child>td]:border-b-0">
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-2.5 text-13 text-ink-3">
            <div class="flex items-center gap-3">
                <span class="tabular">{{ __(':from–:to of :total', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => number_format($paginator->total())]) }}</span>
                <label class="flex items-center gap-1.5 max-sm:hidden">
                    <span>{{ __('Rows') }}</span>
                    <select class="control h-7 w-auto py-0 pe-7 ps-2 text-13" onchange="const u = new URL(location.href); u.searchParams.set('per_page', this.value); u.searchParams.delete('page'); location.href = u">
                        @foreach ([15, 25, 50, 100] as $n)<option @selected($paginator->perPage() === $n)>{{ $n }}</option>@endforeach
                    </select>
                </label>
            </div>
            {{ $paginator->links('components.ui.pagination') }}
        </div>
    @endif

    @if ($bulkDelete)
        {{-- Bulk action bar --}}
        <div x-show="selected.length" x-cloak x-transition.opacity
             class="fixed inset-x-3 bottom-4 z-40 mx-auto flex max-w-xl items-center gap-2 rounded-card bg-ink px-3 py-2 text-sm text-canvas shadow-pop sm:inset-x-0">
            <span class="ps-1 font-medium tabular" x-text="selected.length + ' ' + (selected.length === 1 ? noun[0] : noun[1]) + ' {{ __('selected') }}'"></span>
            <span class="flex-1"></span>
            <button type="button" @click="exportCsv()" class="inline-flex items-center gap-1.5 rounded-control px-2.5 py-1.5 hover:bg-canvas/10"><x-icon name="download" class="size-4" /><span class="max-sm:sr-only">{{ __('Export CSV') }}</span></button>
            <button type="button" @click="$dispatch('confirm', {
                        title: '{{ __('Delete') }} ' + selected.length + ' ' + (selected.length === 1 ? noun[0] : noun[1]) + '?',
                        message: '{{ __('They will be moved out of the list. You can undo this right after.') }}',
                        confirmLabel: '{{ __('Delete') }}', action: @js($bulkDelete), method: 'DELETE', fields: { 'ids[]': selected } })"
                    class="inline-flex items-center gap-1.5 rounded-control px-2.5 py-1.5 text-danger-soft hover:bg-canvas/10"><x-icon name="trash" class="size-4" /><span class="max-sm:sr-only">{{ __('Delete') }}</span></button>
            <button type="button" @click="clear()" class="rounded-control p-1.5 hover:bg-canvas/10" aria-label="{{ __('Clear selection') }}"><x-icon name="x" class="size-4" /></button>
        </div>
    @endif
</div>
