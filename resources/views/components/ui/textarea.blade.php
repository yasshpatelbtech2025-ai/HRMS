{{-- A labelled textarea with help and its validation error. `name` uses dot notation for nested fields. --}}
@props(['name', 'label', 'required' => false, 'value' => null, 'help' => null, 'rows' => 3, 'placeholder' => null])
@php
    $id = 'field-'.str_replace(['.', '_'], '-', $name);
    $segments = explode('.', $name);
    $inputName = array_shift($segments).collect($segments)->map(fn ($s) => "[{$s}]")->implode('');
    $invalid = $errors->has($name);
@endphp
<x-ui.field :label="$label" :for="$id" :name="$name" :required="$required" :help="$help">
    <textarea id="{{ $id }}" name="{{ $inputName }}" rows="{{ $rows }}" @if ($required) required @endif
              @if ($placeholder) placeholder="{{ $placeholder }}" @endif
              @if ($help) aria-describedby="{{ $id }}-help" @endif @if ($invalid) aria-invalid="true" @endif
              {{ $attributes->merge(['class' => 'control px-3 py-2']) }}>{{ old($name, $value) }}</textarea>
</x-ui.field>
