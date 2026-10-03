<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    @php($input = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500')

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Smart attendance') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Marks the chosen employees present at their shift times on every working day of the month that has no punches yet. Week-offs, holidays and days outside their employment are skipped.') }}</p>
        </div>

        <form method="POST" action="{{ route('attendance.smart.store') }}" class="max-w-3xl space-y-4 px-5 py-6"
              x-data="{
                  company: @js((string) old('company_id', '')),
                  selected: @js(old('employees', [])),
                  employees: @js($employees->map(fn ($e) => ['uuid' => $e->uuid, 'company_id' => $e->company_id, 'label' => $e->employee_code.' - '.$e->full_name, 'hasShift' => (bool) $e->shift_id])),
                  get listed() { return this.employees.filter(e => String(e.company_id) === String(this.company)) },
                  toggleAll(on) { this.selected = on ? this.listed.map(e => e.uuid) : [] },
              }">
            @csrf
            <x-form-row :label="__('Company')" for="smart-company" required :messages="$errors->get('company_id')">
                <select id="smart-company" name="company_id" x-model="company" x-on:change="selected = []" required class="{{ $input }}">
                    <option value="">{{ __('Select company') }}</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->label }}</option>
                    @endforeach
                </select>
            </x-form-row>

            <x-form-row :label="__('Employee')" required :messages="array_merge($errors->get('employees'), $errors->get('employees.*'))">
                <div class="rounded-md border border-gray-300">
                    <label class="flex items-center gap-2 border-b border-gray-200 px-3 py-2 text-sm font-medium text-gray-700" x-show="listed.length">
                        <input type="checkbox" x-on:change="toggleAll($event.target.checked)" :checked="listed.length && selected.length === listed.length"
                               class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        {{ __('All employees') }}
                    </label>
                    <div class="max-h-64 overflow-y-auto p-1">
                        <template x-for="e in listed" :key="e.uuid">
                            <label class="flex items-center gap-2 rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-50">
                                <input type="checkbox" name="employees[]" :value="e.uuid" x-model="selected" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span x-text="e.label"></span>
                                <span x-show="! e.hasShift" class="text-xs text-amber-700">({{ __('no shift — will be skipped') }})</span>
                            </label>
                        </template>
                        <p x-show="! listed.length" class="px-2 py-2 text-sm text-gray-500" x-text="company ? @js(__('This company has no current employees.')) : @js(__('Select a company first.'))"></p>
                    </div>
                </div>
            </x-form-row>

            <x-form-row :label="__('Year')" for="smart-year" required :messages="$errors->get('year')">
                <select id="smart-year" name="year" required class="{{ $input }}">
                    @foreach (range((int) date('Y'), 2020) as $year)
                        <option value="{{ $year }}" @selected((int) old('year', date('Y')) === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </x-form-row>

            <x-form-row :label="__('Month')" for="smart-month" required :messages="$errors->get('month')">
                <select id="smart-month" name="month" required class="{{ $input }}">
                    @foreach (range(1, 12) as $month)
                        <option value="{{ $month }}" @selected((int) old('month', date('n')) === $month)>{{ \Carbon\Carbon::create(2000, $month)->format('F') }}</option>
                    @endforeach
                </select>
            </x-form-row>

            <div class="flex justify-end">
                <x-primary-button>{{ __('Submit') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
