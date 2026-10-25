{{-- One section of the employee form, as a sheet: a numbered heading on the ruled margin, then the fields.
     It is also one step of the stepped form (resources/js/ui/form-steps.js). When all sections are on one
     page it can be folded; it opens itself when it holds a validation error. --}}
@props(['title', 'description' => null, 'required' => false, 'open' => false, 'fields' => []])

@php
    $hasError = collect($errors->keys())->contains(fn ($key) => collect($fields)->contains(fn ($field) => $key === $field || str_starts_with($key, $field.'.')));
@endphp

<section x-data="{ open: @js($open || $hasError) }" x-on:form-step-open="open = true"
         data-step-section data-step-title="{{ $title }}" data-has-error="{{ $hasError ? 1 : 0 }}" @if ($required) data-step-required @endif
         class="sheet-section scroll-mt-24 rounded-dialog border bg-surface {{ $hasError ? 'border-danger/50' : 'border-line' }}">
    <h2>
        <button type="button" x-on:click="if (typeof stepped === 'undefined' || ! stepped) open = ! open" :aria-expanded="open"
                class="flex w-full items-start gap-4 rounded-dialog px-5 pb-5 pt-6 text-start sm:px-8 sm:pt-8">
            <span class="sheet-number w-8 shrink-0 pt-1 text-end font-mono text-13 text-ink-3" aria-hidden="true"></span>
            <span class="min-w-0 flex-1 border-s-2 border-primary ps-4">
                <span class="flex flex-wrap items-center gap-2 text-xl font-semibold text-ink">
                    {{ $title }}@if ($required)<span class="text-base text-danger" aria-hidden="true">*</span>@endif
                    @if ($hasError)<x-ui.badge tone="danger">{{ __('Needs attention') }}</x-ui.badge>@endif
                </span>
                @if ($description)<span class="mt-1 block text-sm font-normal text-ink-2">{{ $description }}</span>@endif
            </span>
            <x-icon name="chevron-down" class="mt-1.5 size-5 shrink-0 text-ink-3 transition-transform duration-fast" ::class="open && 'rotate-180'" x-show="typeof stepped === 'undefined' || ! stepped" />
        </button>
    </h2>
    <div x-show="open" x-collapse class="px-5 pb-8 sm:pe-8 sm:ps-[6.125rem]" @unless ($open || $hasError) style="display: none;" @endunless>
        {{ $slot }}
    </div>
</section>
