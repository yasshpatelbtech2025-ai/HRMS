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
    <label for="{{ $id }}" class="block text-13 font-medium text-ink-2">
        {{ $label }} @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
    </label>
    <input id="{{ $id }}" name="{{ $inputName }}" type="{{ $type }}" @if ($type !== 'file') value="{{ $current instanceof \DateTimeInterface ? $current->format('Y-m-d') : $current }}" @endif
           @if ($required) required @endif placeholder="{{ $type === 'file' ? '' : $label }}"
           {{ $attributes->merge(['class' => $type === 'file'
               ? 'mt-1.5 block w-full text-sm text-ink-2 file:me-3 file:rounded-md file:border file:border-line-strong file:bg-surface file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ink hover:file:bg-sunken'
               : 'mt-1.5 block w-full rounded-md border-line-strong bg-surface text-sm text-ink shadow-none placeholder:text-ink-3 focus:border-primary focus:ring-2 focus:ring-primary/20']) }}>
    @if ($hint)
        <p class="mt-1 text-xs text-ink-3">{{ $hint }}</p>
    @endif
    <x-input-error class="mt-1" :messages="$errors->get($name)" />
</div>
