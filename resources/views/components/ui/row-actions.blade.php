{{-- The "⋯" menu at the end of a row. --}}
@props(['label' => __('Row actions')])
<x-ui.menu width="w-44">
    <x-slot:trigger>
        <x-ui.button variant="ghost" size="sm" square type="button" aria-label="{{ $label }}" aria-haspopup="menu"><x-icon name="dots" class="size-4" /></x-ui.button>
    </x-slot:trigger>
    {{ $slot }}
</x-ui.menu>
