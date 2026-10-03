<x-app-layout>
    <x-slot name="header">{{ __('Configs') }}</x-slot>

    <x-section-tabs section="configs" />

    @php
        $select = 'block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
        $yesNo = ['0' => __('No'), '1' => __('Yes')];
        $value = fn (string $field) => old($field, is_bool($setting?->{$field}) ? (int) $setting->{$field} : $setting?->{$field});
        $questions = [
            'probation_period_months' => ['Employee Prohibition Period ? (In Months)', 'number'],
            'notice_period_days' => ['Employee Notice Period ? (In Days)', 'number'],
            'salary_structure' => ['Employee Salary Structure ?', \App\Http\Controllers\PayrollSettingController::STRUCTURES],
            'bonus_frequency' => ['Employee Bouns Paid Frequency ?', \App\Http\Controllers\PayrollSettingController::BONUS_FREQUENCIES],
            'tax_deduction' => ['Employee Tax Deducation ?', $yesNo],
            'lwf_paid_by_company' => ['LWF Contribution by Company ?', $yesNo],
            'pf_effect_on' => ['Employee PF Deduction Effect On ?', config('attendance.effect_on')],
            'esi_effect_on' => ['Employee ESI Deduction Effect On ?', ['gross' => 'On Gross Salary', 'basic' => 'On Basic Salary']],
        ];
    @endphp

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Payroll setting') }}</h2>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('configs.payroll') }}">
                    <label for="payroll-company" class="sr-only">{{ __('Company') }}</label>
                    <select id="payroll-company" name="company" onchange="this.form.submit()" class="{{ $select }} min-w-56">
                        @foreach ($companies as $option)
                            <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                        @endforeach
                    </select>
                </form>
                @if ($company)
                    <x-add-button modal="create-payroll-rule">{{ __('Create setting') }}</x-add-button>
                @endif
            </div>
        </div>

        @if (! $company)
            <p class="px-5 py-10 text-center text-sm text-gray-500">
                {{ __('Add a company first.') }} <a href="{{ route('company.index') }}" class="text-indigo-600 hover:underline">{{ __('Go to Company') }}</a>
            </p>
        @else
            <form method="POST" action="{{ route('configs.payroll.update', $company) }}" class="space-y-6 px-5 py-6">
                @csrf
                @method('PUT')
                <h3 class="text-center text-xl font-semibold text-gray-900">{{ $company->establishment_name }}</h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @foreach ($questions as $field => [$question, $kind])
                        <div class="rounded-lg border border-gray-200 p-4">
                            <label for="payroll-{{ $field }}" class="mb-2 block text-sm font-semibold text-gray-900">{{ __($question) }}</label>
                            @if ($kind === 'number')
                                <input type="number" min="0" id="payroll-{{ $field }}" name="{{ $field }}" value="{{ $value($field) }}" class="{{ $select }}">
                            @else
                                <select id="payroll-{{ $field }}" name="{{ $field }}" class="{{ $select }}">
                                    @foreach ($kind as $option => $label)
                                        <option value="{{ $option }}" @selected((string) $value($field) === (string) $option)>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @if ($field === 'salary_structure')
                                <p class="mt-1 text-xs text-gray-500">{{ __('New Structure limits salary elements to Basic, Allowance and Bonus, with Basic at least half of gross.') }}</p>
                            @endif
                            <x-input-error class="mt-1" :messages="$errors->get($field)" />
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </div>
            </form>

            <div class="border-t border-gray-200">
                <h3 class="px-5 pt-5 text-lg font-semibold text-gray-900">{{ __('Payroll rules') }}</h3>
                <div class="overflow-x-auto">
                    <table class="mt-3 min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                            <tr>
                                <th class="px-5 py-3">{{ __('Sl') }}</th>
                                <th class="px-5 py-3">{{ __('Type') }}</th>
                                <th class="px-5 py-3">{{ __('Name') }}</th>
                                <th class="px-5 py-3">{{ __('Month') }}</th>
                                <th class="px-5 py-3">{{ __('Amount') }}</th>
                                <th class="px-5 py-3">{{ __('Status') }}</th>
                                <th class="px-5 py-3">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rules as $rule)
                                @php $modal = 'edit-payroll-rule-'.$rule->uuid; @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 text-gray-500">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">{{ \App\Models\PayrollRule::TYPES[$rule->type] ?? $rule->type }}</td>
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $rule->name }}</td>
                                    <td class="px-5 py-3">{{ $rule->month->format('F Y') }}</td>
                                    <td class="px-5 py-3">
                                        {{ $rule->is_percent ? rtrim(rtrim($rule->amount, '0'), '.').'% '.__('of :basis', ['basis' => $rule->effect_on]) : number_format((float) $rule->amount, 2) }}
                                    </td>
                                    <td class="px-5 py-3"><x-status-badge :active="$rule->is_active" /></td>
                                    <td class="px-5 py-3">
                                        <x-row-actions :edit-modal="$modal" :delete-url="route('configs.payroll.rules.destroy', $rule)"
                                                       :delete-confirm="__('Delete rule :name?', ['name' => $rule->name])" />
                                        <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="2xl">
                                            @include('configs._payroll-rule-form')
                                        </x-modal>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-8 text-center text-gray-500">{{ __('No payroll rules yet. Use "Create setting" to add a variable bonus.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @if ($company)
        <x-modal name="create-payroll-rule" :show="old('_modal') === 'create-payroll-rule'" max-width="2xl">
            @include('configs._payroll-rule-form', ['rule' => new \App\Models\PayrollRule(['company_id' => $company->id, 'is_active' => true, 'effect_on' => 'basic']), 'modal' => 'create-payroll-rule'])
        </x-modal>
    @endif
</x-app-layout>
