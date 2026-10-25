{{--
    "Select Separation Date" modal, opened by dispatching `separate-employee`
    with { action, name, date }. Leaving the date empty clears it.
--}}
<div x-data="{ target: { action: '', name: '', date: '' } }"
     x-on:separate-employee.window="target = { ...$event.detail, date: $event.detail.date || '' }; $dispatch('open-modal', 'employee-separation')">
    <x-modal name="employee-separation" max-width="md">
        <form method="POST" :action="target.action">
            @csrf
            @method('PUT')
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Select Separation Date') }}</h2>
                <p class="text-sm text-gray-500" x-text="target.name"></p>
            </div>
            <div class="px-6 py-5">
                <label for="separation_date" class="control-label">{{ __('Separation date') }}</label>
                <div class="flex gap-2">
                    <x-text-input type="date" id="separation_date" name="separation_date" x-model="target.date" />
                    <button type="button" x-show="target.date" x-on:click="target.date = ''" title="{{ __('Clear date') }}"
                            class="inline-flex shrink-0 items-center rounded-control border border-line-strong px-3 text-sm font-medium text-ink-2 hover:bg-sunken hover:text-ink">{{ __('Clear') }}</button>
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('separation_date')" />
            </div>
            <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
