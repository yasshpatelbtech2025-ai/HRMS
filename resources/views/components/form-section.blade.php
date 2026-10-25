{{-- A collapsible section of the employee form. Opens itself when it holds a validation error.
     It is also one step of the stepped form (resources/js/ui/form-steps.js). --}}
@props(['title', 'description' => null, 'required' => false, 'open' => false, 'fields' => []])

@php
    $hasError = collect($errors->keys())->contains(fn ($key) => collect($fields)->contains(fn ($field) => $key === $field || str_starts_with($key, $field.'.')));
@endphp

<section x-data="{ open: @js($open || $hasError) }" x-on:form-step-open="open = true"
         data-step-section data-step-title="{{ $title }}" data-has-error="{{ $hasError ? 1 : 0 }}" @if ($required) data-step-required @endif
         class="scroll-mt-24 rounded-card border bg-surface {{ $hasError ? 'border-danger/50' : 'border-line' }}">
    <h2>
        <button type="button" x-on:click="open = ! open" :aria-expanded="open"
                class="flex w-full items-start justify-between gap-4 rounded-card px-5 py-4 text-start sm:px-6">
            <span class="min-w-0">
                <span class="flex flex-wrap items-center gap-2 text-[15px] font-semibold text-ink">
                    {{ $title }}@if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
                    @if ($hasError)<x-ui.badge tone="danger">{{ __('Needs attention') }}</x-ui.badge>@endif
                </span>
                @if ($description)<span class="mt-0.5 block text-13 font-normal text-ink-2">{{ $description }}</span>@endif
            </span>
            <x-icon name="chevron-down" class="mt-0.5 size-5 shrink-0 text-ink-3 transition-transform duration-fast" ::class="open && 'rotate-180'" />
        </button>
    </h2>
    <div x-show="open" x-collapse class="border-t border-line px-5 py-6 sm:px-6" @unless ($open || $hasError) style="display: none;" @endunless>
        {{ $slot }}
    </div>
</section>
