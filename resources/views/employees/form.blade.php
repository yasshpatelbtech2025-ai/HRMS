<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    @php
        $e = $employee;
        $editing = $e->exists;
        $addressTypes = config('employee.address_types');
        $grossLabels = config('employee.gross_elements');
        $deductionLabels = config('employee.deduction_elements');
        $savedSalary = $e->relationLoaded('salaryElements') ? $e->salaryElements->keyBy('element') : collect();
        $salary = collect($grossLabels + $deductionLabels)->map(fn ($label, $key) => [
            'monthly' => old("salary.{$key}.monthly", $savedSalary[$key]->monthly ?? ''),
            'yearly' => old("salary.{$key}.yearly", $key === 'bonus' ? '' : ($savedSalary[$key]->yearly ?? '')),
        ]);
        $hasAmount = fn ($key) => filled($salary[$key]['monthly']) && (float) $salary[$key]['monthly'] > 0;
        $addresses = collect($addressTypes)->map(fn ($label, $type) => collect(['address_line_1', 'address_line_2', 'city', 'village', 'taluka', 'district', 'state', 'country'])
            ->mapWithKeys(fn ($field) => [$field => old("addresses.{$type}.{$field}", $editing ? $e->address($type)?->{$field} : '') ?? '']));
        $experiences = old('experiences', $editing ? $e->experiences->map->only(['company_name', 'designation', 'salary', 'total_years_worked', 'reason_of_leaving'])->values()->all() : []);
        $qualification = fn ($level, $field) => old("qualifications.{$level}.{$field}", $editing ? $e->qualifications->firstWhere('level', $level)?->{$field} : null);
        $family = fn ($relation) => $editing ? $e->familyMembers->firstWhere('relation', $relation) : null;

        $state = [
            'company' => (string) old('company_id', $e->company_id ?? ''),
            'department' => (string) old('department_id', $e->department_id ?? ''),
            'subDepartment' => (string) old('sub_department_id', $e->sub_department_id ?? ''),
            'position' => (string) old('position_id', $e->position_id ?? ''),
            'officer' => (string) old('reporting_officer_id', $e->reporting_officer_id ?? ''),
            'shift' => (string) old('shift_id', $e->shift_id ?? ''),
            'payFrequency' => old('pay_frequency', $e->pay_frequency ?? 'monthly'),
            'hourlyRate' => old('hourly_rate', $e->hourly_rate ?? ''),
            'dailyRate' => old('daily_rate', $e->daily_rate ?? ''),
            'isDisabled' => (string) (int) old('is_disabled', $e->is_disabled ?? 0),
            'permanentSame' => (bool) old('permanent_address_is_present', $e->permanent_address_is_present ?? false),
            'addresses' => $addresses,
            'experiences' => array_values($experiences ?: [['company_name' => '', 'designation' => '', 'salary' => '', 'total_years_worked' => '', 'reason_of_leaving' => '']]),
            'salary' => $salary,
            'grossShown' => collect($grossLabels)->keys()->reject(fn ($k) => $k === 'basic')->filter($hasAmount)->values(),
            'deductionsShown' => collect($deductionLabels)->keys()->filter($hasAmount)->values(),
        ];
        $lookups = [
            'companies' => $companies->map(fn ($c) => ['id' => $c->id, 'employees_count' => $c->employees_count, 'structure' => $c->payrollSetting?->salary_structure ?? 'old']),
            'departments' => $departments,
            'subDepartments' => $subDepartments,
            'positions' => $positions,
            'shifts' => $shifts,
            'officers' => $officers->map(fn ($o) => ['id' => $o->id, 'company_id' => $o->company_id, 'label' => "{$o->employee_code} - {$o->full_name}"]),
        ];
        $yesNo = ['1' => __('Yes'), '0' => __('No')];
        $select = 'mt-1.5 control';
        $cell = 'control';
    @endphp

    <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink">{{ $editing ? __('Edit employee') : __('Add employee') }}</h1>
            @if ($editing)
                <p class="mt-1 text-sm text-ink-3">{{ __('Employee id') }} <span class="font-mono">{{ $e->employee_code }}</span> · {{ $e->full_name }}</p>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg bg-danger-soft px-4 py-3 text-sm text-danger ring-1 ring-red-200">
            {{ trans_choice('There is :count problem with the form. The sections that need attention are open below.|There are :count problems with the form. The sections that need attention are open below.', $errors->count()) }}
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('employees.update', $e) : route('employees.store') }}" enctype="multipart/form-data" novalidate
          x-data="employeeForm(@js(['state' => $state, 'lookups' => $lookups, 'grossLabels' => $grossLabels, 'deductionLabels' => $deductionLabels, 'grossSalaryElements' => config('employee.gross_salary_elements')]))"
          class="space-y-4">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        {{-- Steps (like the first version): adding goes one section at a time; editing shows every section with a jump list. --}}
        <div x-data="formSteps({ stepped: @js(! $editing), remember: @js(! $editing) })" class="grid gap-6 pb-24 lg:grid-cols-[14rem_1fr]">
            <nav aria-label="{{ __('Form sections') }}" class="lg:sticky lg:top-[calc(var(--topbar-h)+1.5rem)] lg:self-start">
                {{-- Phones: a progress bar --}}
                <div class="lg:hidden" x-show="stepped">
                    <div class="mb-1.5 flex items-center justify-between text-13">
                        <span class="font-medium text-ink" x-text="steps[step]?.title"></span>
                        <span class="text-ink-3">{{ __('Step') }} <span x-text="step + 1"></span> {{ __('of') }} <span x-text="steps.length"></span></span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-sunken" role="progressbar" aria-valuemin="1" :aria-valuenow="step + 1" :aria-valuemax="steps.length">
                        <div class="h-full rounded-full bg-primary transition-all duration-300" :style="`width: ${(step + 1) / steps.length * 100}%`"></div>
                    </div>
                </div>

                <ol class="space-y-0.5 max-lg:hidden">
                    <template x-for="(s, i) in steps" :key="i">
                        <li>
                            <button type="button" @click="go(i)" :aria-current="stepped && step === i ? 'step' : null"
                                    :class="stepped && step === i ? 'border-line bg-surface shadow-hairline' : 'border-transparent hover:bg-sunken'"
                                    class="flex w-full items-center gap-3 rounded-md border px-2.5 py-1.5 text-start transition-colors">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-medium transition-colors"
                                      :class="{
                                          'border-danger bg-danger-soft text-danger': state(i) === 'error',
                                          'border-primary bg-primary text-white': state(i) === 'current',
                                          'border-primary/30 bg-primary-soft text-primary': state(i) === 'done',
                                          'border-line-strong text-ink-3': state(i) === 'todo',
                                      }">
                                    <template x-if="state(i) === 'done'"><x-icon name="check" class="size-3.5" stroke="2.5" /></template>
                                    <template x-if="state(i) === 'error'"><span>!</span></template>
                                    <template x-if="! ['done', 'error'].includes(state(i))"><span x-text="i + 1"></span></template>
                                </span>
                                <span class="min-w-0 truncate text-sm" :class="stepped && step === i ? 'font-medium text-ink' : 'text-ink-2'" x-text="s.title"></span>
                                <span x-show="s.required" class="ms-auto text-danger" aria-hidden="true">*</span>
                            </button>
                        </li>
                    </template>
                </ol>

                @unless ($editing)
                    <label class="mt-4 flex cursor-pointer items-center gap-2 px-2.5 text-13 text-ink-3 max-lg:hidden">
                        <input type="checkbox" class="size-4 rounded border-line-strong text-primary focus:ring-primary/30" :checked="! stepped" @change="stepped = ! $event.target.checked">
                        {{ __('Show all sections on one page') }}
                    </label>
                @endunless
            </nav>

            <div class="flex min-w-0 flex-col gap-4">

        {{-- 1. Basic info --}}
        <x-form-section :title="__('Basic Info')" required open :fields="['first_name', 'middle_name', 'last_name', 'gender', 'email', 'phone', 'alternate_phone', 'date_of_birth', 'marital_status', 'country', 'company_id', 'date_of_joining']">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field name="first_name" :label="__('First Name')" required :value="$e->first_name" />
                <x-field name="middle_name" :label="__('Middle Name')" :value="$e->middle_name" />
                <x-field name="last_name" :label="__('Last Name')" required :value="$e->last_name" />
                <x-select-field name="gender" :label="__('Gender')" required :value="$e->gender" :placeholder="__('Select Gender')"
                                :options="collect(\App\Enums\Gender::cases())->reverse()->mapWithKeys(fn ($g) => [$g->value => $g->label()])->all()" />
                <x-field name="email" type="email" :label="__('Email')" required :value="$e->email" />
                <x-field name="phone" type="tel" :label="__('Phone')" required :value="$e->phone" />
                <x-field name="alternate_phone" type="tel" :label="__('Alternate Phone')" :value="$e->alternate_phone" />
                <x-field name="date_of_birth" type="date" :label="__('Date of Birth')" required :value="$e->date_of_birth" />
                <x-select-field name="marital_status" :label="__('Marital Status')" :value="$e->marital_status" :placeholder="__('Select marital status')" :options="config('employee.marital_statuses')" />
                <x-select-field name="country" :label="__('Country')" required :value="$e->country ?? 'India'"
                                :options="array_combine(config('employee.countries'), config('employee.countries'))" />
                <x-select-field name="company_id" :label="__('Company')" required :placeholder="__('Select company')" x-model="company" x-on:change="companyChanged()">
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->label }}</option>
                    @endforeach
                </x-select-field>
                <x-field name="date_of_joining" type="date" :label="__('Joining Date')" required :value="$e->date_of_joining" />
            </div>
        </x-form-section>

        {{-- 3. Address info --}}
        <x-form-section :title="__('Address info')" :fields="['addresses.present', 'addresses.permanent', 'permanent_address_is_present']">
            @foreach (['present', 'permanent'] as $type)
                <div @class(['mt-6 border-t border-gray-100 pt-5' => $type === 'permanent'])>
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __($addressTypes[$type]) }}:</h3>
                        @if ($type === 'permanent')
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="permanent_address_is_present" value="0">
                                <input type="checkbox" name="permanent_address_is_present" value="1" x-model="permanentSame" class="rounded border-field text-primary focus:ring-primary/30">
                                {{ __('Present Address Is Permanent Address') }}
                            </label>
                        @endif
                    </div>
                    @include('employees._address-fields', ['type' => $type, 'readonly' => $type === 'permanent' ? 'permanentSame' : 'false'])
                </div>
            @endforeach
        </x-form-section>

        {{-- 4. Employee docs --}}
        <x-form-section :title="__('Employee Docs')" :fields="['passport_no', 'driving_license_no', 'photo', 'photo_data', 'aadhar_attachment', 'pan_attachment']">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field name="passport_no" :label="__('Passport No')" :value="$e->passport_no" />
                <x-field name="driving_license_no" :label="__('Driving License No')" :value="$e->driving_license_no" />
                @foreach (['aadhar_attachment' => 'Aadhar (attachment)', 'pan_attachment' => 'PAN (attachment)'] as $field => $label)
                    <div>
                        <x-field :name="$field" type="file" :label="__($label)" accept=".pdf,.jpg,.jpeg,.png" :hint="__('PDF, JPG or PNG, up to 5 MB.')" />
                        @if ($e->{$field.'_path'})
                            <a href="{{ route('employees.file', [$e, $field]) }}" target="_blank" class="text-xs text-brand-600 hover:underline">{{ __('View current file') }}</a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6 border-t border-gray-100 pt-5" x-data="photoCapture(@js($e->photo_path ? route('employees.file', [$e, 'photo']) : null))">
                <span class="block text-sm font-medium text-gray-700">{{ __('Profile image') }}</span>
                <div class="mt-2 flex flex-wrap items-start gap-5">
                    <div class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200">
                        <img x-show="preview && ! stream" :src="preview" alt="{{ __('Profile image preview') }}" class="h-full w-full object-cover">
                        <video x-ref="video" x-show="stream" autoplay playsinline muted class="h-full w-full object-cover"></video>
                        <span x-show="! preview && ! stream" class="text-xs text-gray-400">{{ __('No photo') }}</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex flex-wrap gap-2">
                            <label class="cursor-pointer inline-flex h-control-sm items-center justify-center gap-1.5 rounded-control border border-line-strong bg-surface px-3 text-13 font-medium text-ink hover:border-field hover:bg-sunken">
                                {{ __('Choose File') }}
                                <input type="file" accept="image/*" class="sr-only" x-on:change="chooseFile($event)">
                            </label>
                            <button type="button" x-show="! stream" x-on:click="openCamera()" class="inline-flex h-control-sm items-center justify-center gap-1.5 rounded-control border border-line-strong bg-surface px-3 text-13 font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Open Camera') }}</button>
                            <button type="button" x-show="stream" x-on:click="capture()" class="inline-flex h-control-sm items-center justify-center gap-1.5 rounded-control bg-primary px-3 text-13 font-medium text-on-primary hover:bg-primary-hover">{{ __('Capture') }}</button>
                            <button type="button" x-show="data" x-on:click="openCamera()" class="inline-flex h-control-sm items-center justify-center gap-1.5 rounded-control border border-line-strong bg-surface px-3 text-13 font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Retake') }}</button>
                            <button type="button" x-show="data || stream" x-on:click="clear()" class="rounded-md px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</button>
                        </div>
                        <select x-show="cameras.length > 1" x-model.number="cameraIndex" x-on:change="stream && startCamera()" class="rounded-md border-gray-300 text-sm" aria-label="{{ __('Select Camera') }}">
                            <template x-for="(camera, index) in cameras" :key="camera.deviceId"><option :value="index" x-text="camera.label || ('Camera ' + (index + 1))"></option></template>
                        </select>
                        <p class="text-xs text-gray-500" x-text="info || @js(__('Photos are resized to fit in 30 KB.'))"></p>
                        <p class="text-xs text-red-600" x-show="error" x-text="error"></p>
                        <x-input-error :messages="array_merge($errors->get('photo'), $errors->get('photo_data'))" />
                    </div>
                </div>
                <input type="hidden" name="photo_data" :value="data">
            </div>
        </x-form-section>

        {{-- 5. Statutory info --}}
        {{-- Every statutory field is mandatory when adding an employee. --}}
        <x-form-section :title="__('Statutory Info')" :required="! $editing" :fields="['aadhar_card_no', 'pan_card_no', 'uan_code', 'uan_document', 'ip_number', 'nominee_name', 'nominee_relationship', 'nominee_document']">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field name="aadhar_card_no" :label="__('Aadhar No')" :required="! $editing" :value="$e->aadhar_card_no" inputmode="numeric" maxlength="14" />
                <x-field name="pan_card_no" :label="__('PAN No')" :required="! $editing" :value="$e->pan_card_no" class="uppercase" maxlength="10" />
                <x-field name="uan_code" :label="__('UAN No')" :required="! $editing" :value="$e->uan_code" inputmode="numeric" maxlength="12" />
                <x-field name="ip_number" :label="__('ESIC No')" :required="! $editing" :value="$e->ip_number" inputmode="numeric" maxlength="20" />
                <x-field name="nominee_name" :label="__('Nominee Name')" :required="! $editing" :value="$e->nominee_name" />
                <x-field name="nominee_relationship" :label="__('Nominee Relationship')" :required="! $editing" :value="$e->nominee_relationship" list="nominee-relationships" autocomplete="off" />
                {{-- The two uploads share the last row. --}}
                @include('employees._file-field', ['field' => 'uan_document', 'label' => 'UAN Document (attachment)', 'required' => ! $editing])
                @include('employees._file-field', ['field' => 'nominee_document', 'label' => 'Nominee Document (attachment)', 'required' => ! $editing])
            </div>
            <datalist id="nominee-relationships">@foreach (['Father', 'Mother', 'Spouse', 'Son', 'Daughter', 'Brother', 'Sister'] as $relation)<option value="{{ __($relation) }}"></option>@endforeach</datalist>
        </x-form-section>

        {{-- 6. Bank info --}}
        <x-form-section :title="__('Bank Info')" required :fields="['bank_name', 'account_number', 'ifsc_code', 'branch_address', 'micr_code', 'bank_passbook']">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field name="bank_name" :label="__('Bank Name')" required :value="$e->bank_name" />
                <x-field name="account_number" :label="__('Account Number')" required :value="$e->account_number" inputmode="numeric" />
                <x-field name="ifsc_code" :label="__('IFSC Code')" required :value="$e->ifsc_code" class="uppercase" maxlength="11" />
                <x-field name="branch_address" :label="__('Branch Address')" :value="$e->branch_address" />
                <x-field name="micr_code" :label="__('MICR Code')" :value="$e->micr_code" inputmode="numeric" maxlength="9" />
                @include('employees._file-field', ['field' => 'bank_passbook', 'label' => 'Bank Passbook (attachment)'])
            </div>
        </x-form-section>

        {{-- 6. Medical info --}}
        <x-form-section :title="__('Medical Info')" :fields="['blood_group', 'health_condition', 'is_disabled', 'disability_description']">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" x-data="{ disabled: @js((string) (int) old('is_disabled', $e->is_disabled ?? 0)) }">
                <x-select-field name="blood_group" :label="__('Blood Group')" :value="$e->blood_group" :placeholder="__('Select blood group')"
                                :options="array_combine(config('employee.blood_groups'), config('employee.blood_groups'))" />
                <x-field name="health_condition" :label="__('Health Condition')" :value="$e->health_condition" />
                <fieldset>
                    <legend class="block text-sm font-medium text-gray-700">{{ __('Is Disable') }}</legend>
                    <div class="mt-2 flex gap-6 text-sm text-gray-700">
                        <label class="inline-flex items-center gap-2"><input type="radio" name="is_disabled" value="1" x-model="disabled" class="text-brand-600 focus:ring-brand-500"> {{ __('Yes') }}</label>
                        <label class="inline-flex items-center gap-2"><input type="radio" name="is_disabled" value="0" x-model="disabled" class="text-brand-600 focus:ring-brand-500"> {{ __('No') }}</label>
                    </div>
                </fieldset>
                <div x-show="disabled === '1'" class="sm:col-span-2 lg:col-span-3">
                    <x-field name="disability_description" :label="__('Disabilities Desc')" :value="$e->disability_description" />
                </div>
            </div>
        </x-form-section>

        {{-- 7. Qualification details --}}
        <x-form-section :title="__('Qualification details')" :fields="['qualifications', 'job_status']">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                        <tr>
                            <th class="px-3 py-2">{{ __('Qualification details') }}</th>
                            <th class="px-3 py-2">{{ __('School name') }}</th>
                            <th class="px-3 py-2">{{ __('Tenure of the course') }}</th>
                            <th class="px-3 py-2">{{ __('Yrs of passing') }}</th>
                            <th class="px-3 py-2">{{ __('Percentage') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach (config('employee.qualification_levels') as $level => $label)
                            <tr>
                                <th scope="row" class="px-3 py-2 text-left font-medium text-gray-700">{{ __($label) }}</th>
                                @foreach (['school', 'tenure', 'passing_year', 'percentage'] as $field)
                                    <td class="px-3 py-2">
                                        <input name="qualifications[{{ $level }}][{{ $field }}]" value="{{ $qualification($level, $field) }}" class="{{ $cell }}"
                                               aria-label="{{ __($label) }} {{ str_replace('_', ' ', $field) }}">
                                        <x-input-error class="mt-1" :messages="$errors->get('qualifications.'.$level.'.'.$field)" />
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 max-w-xs">
                <x-select-field name="job_status" :label="__('Job status')" :value="$e->job_status" :placeholder="__('Select job status')" :options="config('employee.job_statuses')" />
            </div>
        </x-form-section>

        {{-- 8. Experience --}}
        <x-form-section :title="__('Experience details (in case of experience)')" :fields="['experiences', 'date_of_exit_prev_org']">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                        <tr>
                            <th class="px-3 py-2">{{ __('Company name') }}</th>
                            <th class="px-3 py-2">{{ __('Designation') }}</th>
                            <th class="px-3 py-2">{{ __('Salary') }}</th>
                            <th class="px-3 py-2">{{ __('Total yrs worked') }}</th>
                            <th class="px-3 py-2">{{ __('Reason of leaving') }}</th>
                            <th class="px-3 py-2"><span class="sr-only">{{ __('Action') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(experience, index) in experiences" :key="index">
                            <tr>
                                @foreach (['company_name', 'designation', 'salary', 'total_years_worked', 'reason_of_leaving'] as $field)
                                    <td class="px-3 py-2"><input :name="`experiences[${index}][{{ $field }}]`" x-model="experience.{{ $field }}" class="{{ $cell }}" aria-label="{{ str_replace('_', ' ', $field) }}"></td>
                                @endforeach
                                <td class="px-3 py-2"><button type="button" x-on:click="removeExperience(index)" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">{{ __('Delete') }}</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <x-input-error class="mt-2" :messages="collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'experiences.'))->flatten()->all()" />
            <button type="button" x-on:click="addExperience()" class="mt-3 inline-flex h-control-sm items-center justify-center gap-1.5 rounded-control border border-line-strong bg-surface px-3 text-13 font-medium text-ink hover:border-field hover:bg-sunken">+ {{ __('Add another experience') }}</button>
            <div class="mt-4 max-w-xs">
                <x-field name="date_of_exit_prev_org" type="date" :label="__('Date of exit from the previous organisation (if exp)')" :value="$e->date_of_exit_prev_org" />
            </div>
        </x-form-section>

        {{-- 9. Salary info --}}
        @include('employees._salary-section')

        {{-- 10. Family detail --}}
        <x-form-section :title="__('Family Detail')" :fields="['family', 'addresses.nominee_permanent', 'addresses.nominee_current']">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @foreach (config('employee.family_relations') as $relation => $label)
                    @php $member = $family($relation); @endphp
                    <fieldset class="rounded-md border border-gray-200 p-4">
                        <legend class="px-1 text-sm font-semibold text-gray-900">{{ __(':relation detail', ['relation' => $label]) }}</legend>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <x-field name="family.{{ $relation }}.full_name" :label="__('Full name')" :value="$member?->full_name" />
                            <x-field name="family.{{ $relation }}.date_of_birth" type="date" :label="__('Date Of Birth')" :value="$member?->date_of_birth" />
                            <x-field name="family.{{ $relation }}.aadhar_card_no" :label="__('Aadhar Card No')" :value="$member?->aadhar_card_no" inputmode="numeric" />
                        </div>
                    </fieldset>
                @endforeach
            </div>

            <label class="mt-6 inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" x-on:change="copyNomineeAddresses($event.target.checked)" class="rounded border-field text-primary focus:ring-primary/30">
                {{ __('Nominee address is employee address') }}
            </label>

            @foreach (['nominee_permanent', 'nominee_current'] as $type)
                <div class="mt-5 border-t border-gray-100 pt-5">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ __($addressTypes[$type]) }}</h3>
                    @include('employees._address-fields', ['type' => $type, 'readonly' => 'false'])
                </div>
            @endforeach
        </x-form-section>

        {{-- 11. Attendance card --}}
        <x-form-section :title="__('Attendance card')" :fields="['nfc_card_no']">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-field name="nfc_card_no" :label="__('Nfc card no')" :value="$e->nfc_card_no" />
            </div>
            <p class="mt-3 text-xs text-gray-500">{{ __('Employee self-service logins (email and password) will be added with the employee portal.') }}</p>
        </x-form-section>

            </div>

            {{-- Sticky action bar: Save is always one click away. --}}
            <div class="app-main fixed inset-x-0 bottom-0 z-20 border-t border-line bg-surface/95 backdrop-blur print:hidden">
                <div class="mx-auto flex max-w-[1600px] flex-wrap items-center gap-2 px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ route('employees.index') }}" class="rounded-md px-3 py-2 text-sm font-medium text-ink-2 hover:bg-sunken hover:text-ink">{{ __('Cancel') }}</a>
                    <span class="flex-1"></span>
                    <x-secondary-button x-show="stepped && step > 0" x-on:click="back()"><x-icon name="chevron-left" class="size-4" />{{ __('Back') }}</x-secondary-button>
                    <x-primary-button type="button" x-show="stepped && ! last" x-on:click="next()">{{ __('Next') }}<x-icon name="chevron-right" class="size-4" /></x-primary-button>
                    <x-primary-button x-show="! stepped || last">{{ $editing ? __('Update') : __('Submit') }}</x-primary-button>
                </div>
            </div>
        </div>

        {{-- Suggestions for the address fields; any new value can be typed. --}}
        <datalist id="employee-cities">@foreach ($cities as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
        <datalist id="employee-talukas">@foreach ($talukas as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
        <datalist id="employee-districts">@foreach ($districts as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
        <datalist id="employee-states">@foreach ($states as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
        <datalist id="employee-countries">@foreach ($countries as $option)<option value="{{ $option }}"></option>@endforeach</datalist>
    </form>
</x-app-layout>
