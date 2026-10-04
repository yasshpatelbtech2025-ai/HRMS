{{--
    The bar shown while rows are selected (inside an x-data="bulkSelect" list).
    Props: `action` (POST url for the actions), `actions` (value => [label, style, confirm message or null]),
    `exports` (label => url; the selected ids are added), `noun` (singular|plural for the count).
--}}
@props(['action', 'actions' => [], 'exports' => [], 'noun' => 'record|records'])

@php
    [$one, $many] = explode('|', $noun);
    // Only admins delete.
    $actions = Gate::allows('delete-records') ? $actions : array_diff_key($actions, ['delete' => true]);
    $styles = [
        'default' => 'border border-white/20 bg-white/10 text-white hover:bg-white/20',
        'danger' => 'bg-red-600 text-white hover:bg-red-500',
    ];
@endphp
<div x-show="selected.length" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-4 opacity-0" style="display: none;"
     class="fixed inset-x-0 bottom-4 z-40 flex justify-center px-4 lg:ps-[260px] print:hidden" role="region" aria-label="{{ __('Bulk actions') }}">
    <form method="POST" action="{{ $action }}" class="flex flex-wrap items-center gap-2 rounded-xl bg-gray-900 px-4 py-3 text-sm text-white shadow-xl">
        @csrf
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <span class="me-2 font-semibold" x-text="selected.length === 1 ? @js('1 '.__($one).' '.__('selected')) : selected.length + ' ' + @js(__($many).' '.__('selected'))"></span>
        @foreach ($exports as $label => $url)
            <a :href="withIds(@js($url))" class="rounded-md px-3 py-1.5 font-medium {{ $styles['default'] }}">{{ $label }}</a>
        @endforeach
        @foreach ($actions as $value => [$label, $style, $confirm])
            <button name="action" value="{{ $value }}" class="rounded-md px-3 py-1.5 font-medium {{ $styles[$style] ?? $styles['default'] }}"
                    @if ($confirm) x-on:click="$el.form.dataset.confirm = @js(__($confirm)).replace(':count', selected.length); $el.form.dataset.confirmButton = @js(__($label)); $el.form.toggleAttribute('data-confirm-danger', @js($style === 'danger'))" @else x-on:click="delete $el.form.dataset.confirm" @endif>{{ $label }}</button>
        @endforeach
        <button type="button" x-on:click="clear()" class="ms-1 rounded-md px-2 py-1.5 text-gray-300 hover:text-white">{{ __('Clear') }}</button>
    </form>
</div>
