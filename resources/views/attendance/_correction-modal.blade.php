{{-- "Attendance Correction Range" modal. Expects: $employee, $from, $to. --}}
<x-modal name="correct-raw-data" :show="$errors->has('from_date') || $errors->has('to_date')" max-width="lg">
    <form method="POST" action="{{ route('attendance.raw-data.correct') }}">
        @csrf
        <input type="hidden" name="employee" value="{{ $employee->uuid }}">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Attendance Correction Range') }}</h2>
            <p class="text-sm text-gray-500">{{ __('Re-works the day, shift and missing flags of unlocked punches, e.g. after changing the shift.') }}</p>
        </div>
        <div class="space-y-4 px-6 py-5">
            <x-form-row :label="__('From Date')" for="correct-from" required :messages="$errors->get('from_date')">
                <input type="date" id="correct-from" name="from_date" value="{{ old('from_date', $from->toDateString()) }}" required
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </x-form-row>
            <x-form-row :label="__('To Date')" for="correct-to" required :messages="$errors->get('to_date')">
                <input type="date" id="correct-to" name="to_date" value="{{ old('to_date', $to->toDateString()) }}" required
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </x-form-row>
        </div>
        <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
            <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
            <x-primary-button>{{ __('Correct Data') }}</x-primary-button>
        </div>
    </form>
</x-modal>
