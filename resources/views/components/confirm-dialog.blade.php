{{-- The confirm dialog for forms with data-confirm (resources/js/confirm-dialog.js). --}}
<div x-data="confirmDialog" x-show="open" x-on:keydown.escape.window="cancel()" style="display: none;"
     class="fixed inset-0 z-[65] flex items-end justify-center p-4 sm:items-center print:hidden" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title">
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-gray-900/50" x-on:click="cancel()"></div>
    <div x-show="open" x-transition:enter="ease-out duration-200" x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-start gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="danger ? 'bg-red-100 text-red-600' : 'bg-brand-100 text-brand-700'">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
            </span>
            <div class="min-w-0">
                <h2 id="confirm-dialog-title" class="text-base font-semibold text-gray-900" x-text="danger ? @js(__('Are you sure?')) : @js(__('Please confirm'))"></h2>
                <p class="mt-1 text-sm text-gray-600" x-text="message"></p>
                <p x-show="danger" class="mt-2 text-xs text-gray-500">{{ __('Most deleted records can be restored for 30 days from Configs › Recently deleted.') }}</p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="cancel()" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">{{ __('Cancel') }}</button>
            <button type="button" x-ref="confirm" x-on:click="confirm()" x-text="button"
                    :class="danger ? 'bg-red-600 hover:bg-red-700' : 'bg-brand-600 hover:bg-brand-700'" class="rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm"></button>
        </div>
    </div>
</div>
