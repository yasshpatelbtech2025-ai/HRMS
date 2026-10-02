@php
    $input = fn (string $name) => old($name, $employee->$name instanceof \Carbon\CarbonInterface ? $employee->$name->format('Y-m-d') : $employee->$name);
    $sections = [
        'Personal details' => [
            ['first_name', 'First name', 'text', true],
            ['middle_name', 'Middle name', 'text'],
            ['last_name', 'Last name', 'text'],
            ['father_name', "Father's name", 'text'],
            ['gender', 'Gender', 'select', false, \App\Models\Employee::GENDERS],
            ['date_of_birth', 'Date of birth', 'date'],
            ['marital_status', 'Marital status', 'select', false, \App\Models\Employee::MARITAL_STATUSES],
            ['mobile', 'Mobile no (10 digits)', 'text'],
            ['email', 'Email', 'email'],
            ['address', 'Address', 'textarea'],
        ],
        'Statutory and bank' => [
            ['aadhaar_number', 'Aadhaar no (12 digits)', 'text'],
            ['pan_number', 'PAN', 'text'],
            ['uan_number', 'UAN (PF)', 'text'],
            ['esic_number', 'ESIC no', 'text'],
            ['bank_name', 'Bank name', 'text'],
            ['bank_account_number', 'Account number', 'text'],
            ['ifsc_code', 'IFSC code', 'text'],
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>
    @include('employees._tabs')

    <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}"
          x-data="{ companyId: @js((string) old('company_id', $employee->company_id)), all: @js($departments), departmentId: @js((string) old('department_id', $employee->department_id)), status: @js(old('status', $employee->status)) }"
          class="max-w-4xl space-y-6">
        @csrf
        @if ($employee->exists) @method('PUT') @endif

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __('Employment') }}</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="company_id" :value="__('Company')" /> <span class="text-red-600">*</span>
                    <select id="company_id" name="company_id" x-model="companyId" @change="departmentId = ''" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('Select company') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->establishment_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('company_id')" />
                </div>
                <div>
                    <x-input-label for="employee_code" :value="__('Employee ID')" />
                    <x-text-input id="employee_code" name="employee_code" class="mt-1 block w-full" :value="$input('employee_code')" :placeholder="__('Auto-generated if blank')" />
                    <x-input-error class="mt-2" :messages="$errors->get('employee_code')" />
                </div>
                <div>
                    <x-input-label for="department_id" :value="__('Department')" />
                    <select id="department_id" name="department_id" x-model="departmentId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('None') }}</option>
                        <template x-for="d in all.filter(d => String(d.company_id) === String(companyId))" :key="d.id">
                            <option :value="d.id" x-text="d.name" :selected="String(d.id) === String(departmentId)"></option>
                        </template>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('department_id')" />
                </div>
                <div>
                    <x-input-label for="position_id" :value="__('Designation')" />
                    <select id="position_id" name="position_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected(old('position_id', $employee->position_id) == $position->id)>{{ $position->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('position_id')" />
                </div>
                <div>
                    <x-input-label for="joining_date" :value="__('Joining date')" /> <span class="text-red-600">*</span>
                    <x-text-input id="joining_date" name="joining_date" type="date" class="mt-1 block w-full" :value="$input('joining_date')" required />
                    <x-input-error class="mt-2" :messages="$errors->get('joining_date')" />
                </div>
                <div>
                    <x-input-label for="gross_salary" :value="__('Gross salary (monthly)')" />
                    <x-text-input id="gross_salary" name="gross_salary" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="$input('gross_salary')" />
                    <x-input-error class="mt-2" :messages="$errors->get('gross_salary')" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" x-model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="separated">{{ __('Separated') }}</option>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('status')" />
                </div>
                <div x-show="status === 'separated'" x-cloak class="grid grid-cols-1 gap-5 sm:col-span-2 sm:grid-cols-2">
                    <div>
                        <x-input-label for="separation_date" :value="__('Separation date')" /> <span class="text-red-600">*</span>
                        <x-text-input id="separation_date" name="separation_date" type="date" class="mt-1 block w-full" :value="$input('separation_date')" />
                        <x-input-error class="mt-2" :messages="$errors->get('separation_date')" />
                    </div>
                    <div>
                        <x-input-label for="separation_reason" :value="__('Reason')" />
                        <x-text-input id="separation_reason" name="separation_reason" class="mt-1 block w-full" :value="$input('separation_reason')" />
                        <x-input-error class="mt-2" :messages="$errors->get('separation_reason')" />
                    </div>
                </div>
            </div>
        </section>

        @foreach ($sections as $heading => $fields)
            <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ __($heading) }}</h2>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    @foreach ($fields as $field)
                        @php [$name, $label, $type, $required, $options] = array_pad($field, 5, null); @endphp
                        <div class="{{ $type === 'textarea' ? 'sm:col-span-2' : '' }}">
                            <x-input-label :for="$name" :value="__($label)" />@if ($required ?? false) <span class="text-red-600">*</span>@endif
                            @if ($type === 'select')
                                <select id="{{ $name }}" name="{{ $name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="">{{ __('Select') }}</option>
                                    @foreach ($options as $value => $text)
                                        <option value="{{ $value }}" @selected($input($name) === $value)>{{ __($text) }}</option>
                                    @endforeach
                                </select>
                            @elseif ($type === 'textarea')
                                <textarea id="{{ $name }}" name="{{ $name }}" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $input($name) }}</textarea>
                            @else
                                <x-text-input :id="$name" :name="$name" :type="$type" class="mt-1 block w-full" :value="$input($name)" :required="$required ?? false" />
                            @endif
                            <x-input-error class="mt-2" :messages="$errors->get($name)" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex items-center gap-3">
            <x-primary-button>{{ $employee->exists ? __('Save changes') : __('Add employee') }}</x-primary-button>
            <a href="{{ route('employees.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-app-layout>
