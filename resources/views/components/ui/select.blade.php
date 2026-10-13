{{-- Native select. Pass `options` (value => label) or option tags in the slot. --}}
@props(['name', 'id' => null, 'options' => [], 'value' => null, 'placeholder' => null])
@php
    $id ??= $name;
    $selected = (string) old($name, $value);
@endphp
<select name="{{ $name }}" id="{{ $id }}"
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class(['control pe-9']) }}>
    @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
    @foreach ($options as $optionValue => $label)
        <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $label }}</option>
    @endforeach
    {{ $slot }}
</select>
