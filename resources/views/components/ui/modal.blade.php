{{-- Opened with $dispatch('open-modal', 'name'), closed with 'close-modal' or Esc. --}}
@props(['name', 'title' => null, 'show' => false, 'maxWidth' => 'md'])
@php
    $width = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl'][$maxWidth];
@endphp
<div x-data="{ show: @js($show) }"
     x-on:open-modal.window="$event.detail == '{{ $name }}' && (show = true)"
     x-on:close-modal.window="$event.detail == '{{ $name }}' && (show = false)"
     x-on:close.stop="show = false"
     x-on:keydown.escape.window="show = false"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4">
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-black/40 backdrop-blur-[1px]" @click="show = false"></div>
    <div x-show="show" x-trap.inert.noscroll="show" role="dialog" aria-modal="true" @if ($title) aria-label="{{ $title }}" @endif
         x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[.97]" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-100" x-transition:leave-end="opacity-0"
         {{ $attributes->class(['relative w-full rounded-t-card bg-surface shadow-pop sm:rounded-card', $width]) }}>
        @if ($title)
            <header class="flex items-center justify-between border-b border-line px-5 py-3.5">
                <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
                <x-ui.button variant="ghost" size="sm" square type="button" @click="show = false" aria-label="{{ __('Close') }}"><x-icon name="x" class="size-4" /></x-ui.button>
            </header>
        @endif
        {{ $slot }}
    </div>
</div>
