{{-- The confirm dialog for forms with data-confirm (resources/js/confirm-dialog.js). --}}
<div x-data="confirmDialog" x-show="open" x-on:keydown.escape.window="cancel()" style="display: none;"
     class="fixed inset-0 z-[65] flex items-end justify-center p-3 sm:items-center sm:p-4 print:hidden" role="alertdialog" aria-modal="true" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message">
    <div x-show="open" x-transition.opacity.duration.150ms class="absolute inset-0 bg-ink/40 dark:bg-black/60" x-on:click="cancel()"></div>
    <div x-show="open" x-trap.noscroll="open" x-transition:enter="ease-out duration-200" x-transition:enter-start="translate-y-3 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="relative w-full max-w-md rounded-dialog border border-line bg-surface p-5 shadow-xl">
        <div class="flex items-start gap-4">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full" :class="danger ? 'bg-danger-soft text-danger' : 'bg-primary-soft text-primary'">
                <x-icon name="alert" class="size-5" />
            </span>
            <div class="min-w-0">
                <h2 id="confirm-dialog-title" class="pt-1 text-base font-semibold text-ink" x-text="danger ? @js(__('Are you sure?')) : @js(__('Please confirm'))"></h2>
                <p id="confirm-dialog-message" class="mt-1 text-sm text-ink-2" x-text="message"></p>
                <p x-show="danger" class="mt-2 text-xs text-ink-3">{{ __('Most deleted records can be restored for 30 days from Configs › Recently deleted.') }}</p>
            </div>
        </div>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" x-on:click="cancel()" class="inline-flex h-control items-center justify-center rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:bg-sunken">{{ __('Cancel') }}</button>
            <button type="button" x-ref="confirm" x-on:click="confirm()" x-text="button"
                    :class="danger ? 'bg-danger hover:bg-danger-hover' : 'bg-primary hover:bg-primary-hover'" class="inline-flex h-control items-center justify-center rounded-control px-4 text-sm font-medium text-on-primary"></button>
        </div>
    </div>
</div>
