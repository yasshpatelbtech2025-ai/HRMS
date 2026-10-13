{{-- Label + control + hint + error. The control goes in the slot and should use the same id as `for`.
     The error line is also the target for inline (client-side) validation messages. --}}
@props(['label' => null, 'for' => null, 'hint' => null, 'error' => null, 'bag' => 'default', 'required' => false, 'tag' => 'div'])
@php
    $key = $error ?? $for;
    $message = $key ? $errors->getBag($bag)->first($key) : null;
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        @if ($tag === 'fieldset')
            <legend class="mb-1.5 flex items-baseline gap-1 text-13 font-medium text-ink-2">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif</legend>
        @else
            <label for="{{ $for }}" class="flex items-baseline gap-1 text-13 font-medium text-ink-2">
                {{ $label }}
                @if ($required)<span class="text-danger" aria-hidden="true">*</span><span class="sr-only">({{ __('required') }})</span>@endif
            </label>
        @endif
    @endif

    {{ $slot }}

    <p id="{{ $for }}-error" data-error-for="{{ $for }}" @class(['flex items-start gap-1 text-13 text-danger', 'hidden' => ! $message]) role="alert">
        <x-icon name="alert" class="mt-px size-4" /><span>{{ $message }}</span>
    </p>
    @if ($hint)
        <p id="{{ $for }}-hint" class="text-xs text-ink-3">{{ $hint }}</p>
    @endif
</{{ $tag }}>
