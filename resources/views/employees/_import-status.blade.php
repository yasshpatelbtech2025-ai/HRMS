{{--
    The signed-in user's latest Excel import (App\Jobs\ImportEmployees): a
    progress card while it runs (polled every few seconds), then its outcome.
    Expects: $run.
--}}
<div x-data="{
        status: @js($run->status), finished: @js($run->isFinished()), imported: @js($run->imported), errors: @js($run->errors ?? []), hidden: false,
        init() { if (! this.finished) this.poll() },
        async poll() {
            await new Promise(resolve => setTimeout(resolve, 3000));
            try {
                const response = await fetch(@js(route('employees.import.status', $run)), { headers: { Accept: 'application/json' } });
                if (response.ok) Object.assign(this, await response.json());
            } catch (e) {}
            this.finished ? (this.status === 'done' && window.location.reload()) : this.poll();
        },
     }" x-show="! hidden" role="status" aria-live="polite" class="mb-6 rounded-lg px-4 py-3 text-sm ring-1"
     :class="{ 'bg-gray-50 text-gray-800 ring-gray-200': ! finished, 'bg-success-soft text-emerald-900 ring-emerald-200': status === 'done', 'bg-danger-soft text-danger ring-red-200': status === 'invalid' || status === 'failed' }">
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-2 font-medium">
            <svg x-show="! finished" class="h-4 w-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path></svg>
            <span x-show="! finished">{{ __('Importing :file into :company… you can keep working; the list refreshes when it is done.', ['file' => $run->original_name, 'company' => $run->company?->establishment_name]) }}</span>
            <span x-show="status === 'done'" x-text="@js(__('Import finished: :file.', ['file' => $run->original_name])) + ' ' + imported + ' ' + (imported === 1 ? @js(__('employee imported.')) : @js(__('employees imported.')))"></span>
            <span x-show="status === 'invalid' || status === 'failed'">{{ __('Nothing was imported from :file. Fix the rows below and upload the file again.', ['file' => $run->original_name]) }}</span>
        </div>
        <button type="button" x-show="finished" x-on:click="hidden = true" class="text-current opacity-60 hover:opacity-100" aria-label="{{ __('Dismiss') }}">✕</button>
    </div>
    <ul x-show="errors.length" class="mt-2 list-disc space-y-1 ps-5">
        <template x-for="message in errors" :key="message"><li x-text="message"></li></template>
    </ul>
</div>
