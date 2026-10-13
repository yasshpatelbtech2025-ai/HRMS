{{--
    A labelled <select> with its validation error. `options` maps value =>
    label; pass a slot instead for options rendered by Alpine.
--}}
@props(['name', 'label', 'options' => [], 'required' => false, 'value' => null, 'placeholder' => null])

@php
    $id = 'field-'.str_replace(['.', '_'], '-', $name);
    $segments = explode('.', $name);
    $inputName = array_shift($segments).collect($segments)->map(fn ($s) => "[{$s}]")->implode('');
    $current = (string) (old($name, $value instanceof \BackedEnum ? $value->value : (is_bool($value) ? (int) $value : $value)) ?? '');
@endphp

<div>
    <label for="{{ $id }}" class="block text-13 font-medium text-ink-2">
        {{ $label }} @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
    </label>
    <select id="{{ $id }}" name="{{ $inputName }}" @if ($required) required @endif
            {{ $attributes->merge(['class' => 'mt-1.5 block w-full rounded-md border-line-strong bg-surface text-sm text-ink shadow-none placeholder:text-ink-3 focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:bg-sunken']) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
    <x-input-error class="mt-1" :messages="$errors->get($name)" />
</div>
