{{-- A label-left / field-right row in a modal form, with its validation errors. --}}
@props(['label', 'for' => null, 'required' => false, 'messages' => []])

<div {{ $attributes->merge(['class' => 'lg:grid lg:grid-cols-4 lg:items-start lg:gap-4']) }}>
    <label @if ($for) for="{{ $for }}" @endif class="block pt-2 text-13 font-medium text-ink-2">
        {{ $label }} @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
    </label>
    <div class="mt-1 lg:col-span-3 lg:mt-0">
        {{ $slot }}
        <x-input-error class="mt-2" :messages="$messages" />
    </div>
</div>
