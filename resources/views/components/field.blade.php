{{--
    A labelled form input with its validation error. `name` uses dot
    notation for nested fields (e.g. "addresses.present.village"); the
    submitted name and old input follow from it.
--}}
@props(['name', 'label', 'type' => 'text', 'required' => false, 'value' => null, 'hint' => null])

@php
    $id = 'field-'.str_replace(['.', '_'], '-', $name);
    $segments = explode('.', $name);
    $inputName = array_shift($segments).collect($segments)->map(fn ($s) => "[{$s}]")->implode('');
    $current = old($name, $value);
@endphp

<div>
    <label for="{{ $id }}" class="block text-[15px] font-bold text-gray-700">
        {{ $label }} @if ($required)<span class="text-red-600">*</span>@endif
    </label>
    <input id="{{ $id }}" name="{{ $inputName }}" type="{{ $type }}" @if ($type !== 'file') value="{{ $current instanceof \DateTimeInterface ? $current->format('Y-m-d') : $current }}" @endif
           @if ($required) required @endif placeholder="{{ $type === 'file' ? '' : $label }}"
           {{ $attributes->merge(['class' => $type === 'file'
               ? 'mt-1 block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200'
               : 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500']) }}>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
    <x-input-error class="mt-1" :messages="$errors->get($name)" />
</div>
