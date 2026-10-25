@props(['modal'])

<button type="button" x-data x-on:click="$dispatch('open-modal', @js($modal))"
        {{ $attributes->merge(['class' => 'inline-flex h-control items-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover print:hidden']) }}>
    <x-icon name="plus" class="size-4" />
    {{ $slot }}
</button>
