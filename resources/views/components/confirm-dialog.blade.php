{{-- The confirm dialog for forms with data-confirm (resources/js/confirm-dialog.js). --}}
<div x-data="confirmDialog" x-show="open" x-on:keydown.escape.window="cancel()" style="display: none;"
     class="fixed inset-0 z-[65] flex items-end justify-center p-4 sm:items-center print:hidden" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title">
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/40" x-on:click="cancel()"></div>
    <div x-show="open" x-transition:enter="ease-out duration-200" x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="relative w-full max-w-md rounded-xl bg-surface p-5 shadow-lg">
        <div class="flex items-start gap-4">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="danger ? 'bg-danger-soft text-danger' : 'bg-primary-soft text-primary'">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
            </span>
            <div class="min-w-0">
                <h2 id="confirm-dialog-title" class="pt-1 text-base font-semibold text-ink" x-text="danger ? @js(__('Are you sure?')) : @js(__('Please confirm'))"></h2>
                <p class="mt-1 text-sm text-ink-2" x-text="message"></p>
                <p x-show="danger" class="mt-2 text-xs text-ink-3">{{ __('Most deleted records can be restored for 30 days from Configs › Recently deleted.') }}</p>
            </div>
        </div>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="cancel()" class="inline-flex h-9 items-center justify-center rounded-md border border-line-strong bg-surface px-3.5 text-sm font-medium text-ink shadow-hairline hover:bg-sunken">{{ __('Cancel') }}</button>
            <button type="button" x-ref="confirm" x-on:click="confirm()" x-text="button"
                    :class="danger ? 'bg-danger hover:bg-danger-hover' : 'bg-primary hover:bg-primary-hover'" class="inline-flex h-9 items-center justify-center rounded-md px-3.5 text-sm font-medium text-white shadow-hairline"></button>
        </div>
    </div>
</div>
