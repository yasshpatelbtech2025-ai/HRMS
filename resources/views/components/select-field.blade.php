{{--
    A labelled <select> with help and its validation error. `options` maps value =>
    label; pass a slot instead for options rendered by Alpine.
--}}
@props(['name', 'label', 'options' => [], 'required' => false, 'value' => null, 'placeholder' => null, 'help' => null, 'optional' => false])

@php
    $id = 'field-'.str_replace(['.', '_'], '-', $name);
    $segments = explode('.', $name);
    $inputName = array_shift($segments).collect($segments)->map(fn ($s) => "[{$s}]")->implode('');
    $current = (string) (old($name, $value instanceof \BackedEnum ? $value->value : (is_bool($value) ? (int) $value : $value)) ?? '');
    $invalid = $errors->has($name);
@endphp

<x-ui.field :label="$label" :for="$id" :name="$name" :required="$required" :help="$help" :optional="$optional">
    <select id="{{ $id }}" name="{{ $inputName }}" @if ($required) required @endif
            @if ($help) aria-describedby="{{ $id }}-help" @endif @if ($invalid) aria-invalid="true" @endif
            {{ $attributes->merge(['class' => 'control ps-3 pe-9']) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
</x-ui.field>
