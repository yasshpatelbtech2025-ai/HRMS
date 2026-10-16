@props(['modal'])

<button type="button" x-data x-on:click="$dispatch('open-modal', @js($modal))"
        class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-3.5 text-sm font-medium text-white shadow-hairline hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-1 print:hidden">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
    {{ $slot }}
</button>
