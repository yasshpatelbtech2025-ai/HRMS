{{-- One field in a pop-up form: label above, the control, optional help and its validation errors. --}}
@props(['label', 'for' => null, 'required' => false, 'messages' => [], 'help' => null])

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <label @if ($for) for="{{ $for }}" @endif class="control-label">
        {{ $label }}@if ($required)<span class="ms-0.5 text-danger" aria-hidden="true">*</span><span class="sr-only"> ({{ __('required') }})</span>@endif
    </label>
    {{ $slot }}
    @if ($help)<p class="control-help">{{ $help }}</p>@endif
    <x-input-error class="mt-1.5" :messages="$messages" />
</div>
