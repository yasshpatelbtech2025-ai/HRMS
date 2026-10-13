{{--
    One dialog for every destructive action. Trigger from anywhere:
    $dispatch('confirm', { title, message, confirmLabel, action, method: 'DELETE', fields: { 'ids[]': [...] } })
--}}
<div x-data="confirmDialog" @confirm.window="ask($event.detail)" @keydown.escape.window="open && cancel()"
     x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center sm:p-4">
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/40" @click="cancel()"></div>
    <div x-show="open" x-trap.inert.noscroll="open" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message"
         x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[.97]" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         class="relative w-full rounded-t-card bg-surface p-5 shadow-pop sm:max-w-md sm:rounded-card">
        <div class="flex gap-3.5">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-danger-soft text-danger"><x-icon name="alert" class="size-5" /></span>
            <div class="min-w-0 pt-1">
                <h2 id="confirm-title" class="text-base font-semibold text-ink" x-text="title"></h2>
                <p id="confirm-message" class="mt-1 text-sm text-ink-2" x-text="message"></p>
            </div>
        </div>
        <form x-ref="form" method="POST" :action="action" class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <template x-for="field in hiddenFields"><input type="hidden" :name="field.name" :value="field.value"></template>
            <x-ui.button variant="secondary" type="button" @click="cancel()">{{ __('Cancel') }}</x-ui.button>
            <x-ui.button variant="danger" x-ref="confirm"><span x-text="confirmLabel"></span></x-ui.button>
        </form>
    </div>
</div>
