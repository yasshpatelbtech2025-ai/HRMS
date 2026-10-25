{{--
    The frame around any form control: label above, required mark, one line of
    help, and the server's error for `name` (dot notation). Put the control in the
    slot and give it id="{{ $for }}". Most forms use x-field / x-select-field,
    which use this frame.
--}}
@props(['label' => null, 'for' => null, 'name' => null, 'required' => false, 'help' => null, 'optional' => false])
@php
    $messages = $name ? $errors->get($name) : [];
@endphp
<div {{ $attributes->class(['min-w-0']) }} @if ($messages) data-has-error @endif>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="control-label">
            {{ $label }}@if ($required)<span class="ms-0.5 text-danger" aria-hidden="true">*</span><span class="sr-only"> ({{ __('required') }})</span>@endif
            @if ($optional)<span class="ms-1 font-normal text-ink-3">{{ __('(optional)') }}</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if ($help)
        <p @if ($for) id="{{ $for }}-help" @endif class="control-help">{{ $help }}</p>
    @endif
    <x-input-error class="mt-1.5" :messages="$messages" />
</div>
