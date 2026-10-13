{{-- Toasts replace page-top flash banners. Server flashes (status, error, undo) are picked up on load;
     JS can add more with Alpine.store('toasts').push({ type, message }). --}}
@php
    // Breeze flashes status codes; turn them into sentences.
    $status = match (session('status')) {
        'profile-updated' => __('Profile saved.'),
        'password-updated' => __('Password changed.'),
        'verification-link-sent' => __('A new verification link has been sent to your email.'),
        default => session('status'),
    };
    $initial = array_values(array_filter([
        $status ? ['type' => 'success', 'message' => $status, 'undo' => session('undo')] : null,
        session('error') ? ['type' => 'error', 'message' => session('error')] : null,
    ]));
@endphp
<div x-data x-init="$store.toasts.load(@js($initial))"
     aria-live="polite" aria-atomic="false"
     class="pointer-events-none fixed inset-x-0 bottom-0 z-[70] flex flex-col items-center gap-2 p-4 sm:inset-x-auto sm:end-0 sm:items-end">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div x-show="toast.visible" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0"
             @mouseenter="$store.toasts.pause(toast)" @mouseleave="$store.toasts.resume(toast)"
             :role="toast.type === 'error' ? 'alert' : 'status'"
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-card border border-line bg-surface p-3.5 shadow-pop">
            <span :class="toast.type === 'error' ? 'text-danger' : 'text-success'" class="mt-px">
                <template x-if="toast.type === 'error'"><x-icon name="x-circle" /></template>
                <template x-if="toast.type !== 'error'"><x-icon name="check-circle" /></template>
            </span>
            <p class="min-w-0 flex-1 text-sm text-ink" x-text="toast.message"></p>
            <template x-if="toast.undo">
                <form method="POST" :action="toast.undo">
                    @csrf
                    <input type="hidden" name="_method" value="PATCH">
                    <button class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-13 font-semibold text-primary hover:bg-primary-soft"><x-icon name="undo" class="size-3.5" />{{ __('Undo') }}</button>
                </form>
            </template>
            <button type="button" @click="$store.toasts.dismiss(toast)" class="-m-1 rounded p-1 text-ink-3 hover:bg-sunken hover:text-ink" aria-label="{{ __('Dismiss') }}"><x-icon name="x" class="size-4" /></button>
        </div>
    </template>
</div>
