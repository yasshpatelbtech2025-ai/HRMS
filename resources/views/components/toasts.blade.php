{{-- Toast notifications: this request's flash messages, and any sent with a "toast" window event (resources/js/toasts.js). --}}
@php
    $initial = collect([
        session('status') ? ['type' => 'success', 'message' => session('status'), 'undo' => session('undo')] : null,
        session('error') ? ['type' => 'error', 'message' => session('error')] : null,
    ])->filter()->values();
@endphp
<div x-data="toasts(@js($initial))" aria-live="polite"
     class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:inset-x-auto sm:end-0 sm:items-end sm:p-5 print:hidden">
    {{-- Without JavaScript the messages still show. --}}
    <noscript>
        @foreach ($initial as $toast)
            <div class="pointer-events-auto rounded-lg border border-line bg-surface px-4 py-3 text-sm shadow-pop">{{ $toast['message'] }}</div>
        @endforeach
    </noscript>
    <template x-for="toast in items" :key="toast.id">
        <div x-show="toast.shown" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-2 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             :role="toast.type === 'error' ? 'alert' : 'status'"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-line bg-surface p-3.5 shadow-pop">
            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                  :class="{ 'bg-success-soft text-success': toast.type === 'success', 'bg-danger-soft text-danger': toast.type === 'error', 'bg-info-soft text-info': toast.type === 'info' }">
                <svg x-show="toast.type === 'success'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                <svg x-show="toast.type === 'error'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01" /></svg>
                <svg x-show="toast.type === 'info'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8h.01M11 12h1v5h1" /></svg>
            </span>
            <p class="flex-1 pt-0.5 text-sm text-ink" x-text="toast.message"></p>
            <button type="button" x-show="toast.undo" x-on:click="undo(toast)" class="-my-0.5 rounded-md px-2 py-1 text-13 font-semibold text-primary hover:bg-primary-soft">{{ __('Undo') }}</button>
            <button type="button" x-on:click="remove(toast.id)" class="-m-1 rounded-md p-1 text-ink-3 hover:bg-sunken hover:text-ink" aria-label="{{ __('Dismiss') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </template>
</div>
