{{--
    Searchable single select. Submits `name` through a hidden input.
    options:      [['value' => 1, 'label' => 'Alpha'], ...] (static), or
    options-from: an Alpine expression evaluated in the parent scope (for dependent lists).
    Supports x-model on the component (x-modelable).
--}}
@props(['name', 'id' => null, 'options' => [], 'optionsFrom' => null, 'value' => null, 'placeholder' => 'Select…', 'emptyLabel' => null, 'rules' => null, 'label' => null])
@php
    $id ??= $name;
    $invalid = $errors->has($name);
    $value = old($name, $value);
@endphp

<div x-data="combobox({ value: @js($value === null ? '' : (string) $value), emptyLabel: @js($emptyLabel) })"
     x-modelable="value"
     @if ($optionsFrom) x-effect="source = ({{ $optionsFrom }})" @else x-init="source = @js(array_values($options))" @endif
     @click.outside="close()"
     {{ $attributes->class(['relative']) }}>
    <input type="hidden" name="{{ $name }}" :value="value">
    <div class="relative">
        <input type="text" id="{{ $id }}" x-ref="input" autocomplete="off"
               role="combobox" aria-autocomplete="list" :aria-expanded="open" aria-controls="{{ $id }}-listbox"
               :aria-activedescendant="open && filtered[active] ? '{{ $id }}-opt-' + active : null"
               @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
               placeholder="{{ $placeholder }}"
               @if ($rules) data-rules="{{ $rules }}" data-label="{{ $label }}" data-value-from="{{ $name }}" @endif
               :value="open ? query : selectedLabel"
               @focus="openList()" @click="openList()"
               @input="query = $event.target.value; active = 0; open = true"
               @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
               @keydown.enter.prevent="choose(filtered[active])" @keydown.escape="close()" @keydown.tab="close()"
               class="control pe-9">
        <x-icon name="chevron-up-down" class="pointer-events-none absolute end-2.5 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
    </div>
    <ul id="{{ $id }}-listbox" role="listbox" x-show="open" x-transition.opacity.duration.100ms x-cloak x-ref="list"
        class="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-control border border-line bg-surface py-1 shadow-pop">
        <template x-for="(option, i) in filtered" :key="option.value">
            <li :id="'{{ $id }}-opt-' + i" role="option" :aria-selected="String(option.value) === String(value)"
                @mousedown.prevent="choose(option)" @mousemove="active = i"
                :class="i === active ? 'bg-primary-soft text-primary-ink' : 'text-ink'"
                class="flex cursor-pointer items-center justify-between gap-2 px-3 py-1.5 text-sm">
                <span x-text="option.label" :class="option.value === '' && 'text-ink-3'"></span>
                <x-icon name="check" class="size-4 text-primary" x-show="String(option.value) === String(value)" />
            </li>
        </template>
        <li x-show="filtered.length === 0" class="px-3 py-2 text-13 text-ink-3">{{ __('No matches') }}</li>
    </ul>
</div>
