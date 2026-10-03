{{-- Toast notifications: this request's flash messages, and any sent with a "toast" window event (resources/js/toasts.js). --}}
@php
    $initial = collect([
        session('status') ? ['type' => 'success', 'message' => session('status')] : null,
        session('error') ? ['type' => 'error', 'message' => session('error')] : null,
    ])->filter()->values();
@endphp
<div x-data="toasts(@js($initial))" aria-live="polite"
     class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:inset-x-auto sm:bottom-auto sm:end-0 sm:top-0 sm:items-end sm:p-6 print:hidden">
    {{-- Without JavaScript the messages still show. --}}
    <noscript>
        @foreach ($initial as $toast)
            <div class="pointer-events-auto rounded-lg bg-white px-4 py-3 text-sm shadow-md ring-1 ring-gray-200">{{ $toast['message'] }}</div>
        @endforeach
    </noscript>
    <template x-for="toast in items" :key="toast.id">
        <div x-show="toast.shown" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             :role="toast.type === 'error' ? 'alert' : 'status'"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg bg-white p-4 shadow-lg ring-1 ring-gray-200">
            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                  :class="{ 'bg-emerald-100 text-emerald-700': toast.type === 'success', 'bg-red-100 text-red-700': toast.type === 'error', 'bg-brand-100 text-brand-700': toast.type === 'info' }">
                <svg x-show="toast.type === 'success'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                <svg x-show="toast.type === 'error'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01" /></svg>
                <svg x-show="toast.type === 'info'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8h.01M11 12h1v5h1" /></svg>
            </span>
            <p class="flex-1 text-sm text-gray-800" x-text="toast.message"></p>
            <button type="button" x-on:click="remove(toast.id)" class="-m-1 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="{{ __('Dismiss') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </template>
</div>
