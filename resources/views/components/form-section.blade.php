{{-- A collapsible section of the employee form. Opens itself when it holds a validation error. --}}
@props(['title', 'required' => false, 'open' => false, 'fields' => []])

@php
    $hasError = collect($errors->keys())->contains(fn ($key) => collect($fields)->contains(fn ($field) => $key === $field || str_starts_with($key, $field.'.')));
@endphp

<section x-data="{ open: @js($open || $hasError) }" class="rounded-lg bg-white shadow-sm ring-1 {{ $hasError ? 'ring-red-300' : 'ring-gray-200' }}">
    <h2>
        <button type="button" x-on:click="open = ! open" :aria-expanded="open"
                class="flex w-full items-center justify-between px-5 py-4 text-start text-base font-semibold text-gray-900">
            <span>{{ $title }} @if ($required)<span class="text-red-600">*</span>@endif
                @if ($hasError)<span class="ms-2 text-xs font-medium text-red-600">{{ __('Needs attention') }}</span>@endif
            </span>
            <svg class="h-5 w-5 text-gray-400 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
        </button>
    </h2>
    <div x-show="open" x-transition class="border-t border-gray-200 px-5 py-5" @unless ($open || $hasError) style="display: none;" @endunless>
        {{ $slot }}
    </div>
</section>
