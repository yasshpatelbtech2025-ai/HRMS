<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    @php
        $e = $employee;
        $salary = $e->salaryElements->keyBy('element');
        $labels = config('employee.gross_elements') + config('employee.deduction_elements');
        $yesNo = fn (?bool $value) => $value === null ? '' : ($value ? __('Yes') : __('No'));
        $date = fn ($value) => $value?->format('d-m-Y');
        $masked = fn (?string $value) => $value ? str_repeat('•', max(0, strlen($value) - 4)).substr($value, -4) : '';
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('employees.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Back to employee list') }}</a>
        <div class="flex gap-2">
            <a href="{{ route('employees.edit', $e) }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Edit') }}</a>
            <button type="button" onclick="window.print()" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('Print') }}</button>
        </div>
    </div>

    <article class="mx-auto max-w-4xl rounded-lg bg-white p-6 text-sm shadow-sm ring-1 ring-gray-200 print:max-w-none print:p-0 print:shadow-none print:ring-0">
        <header class="mb-6 flex items-start justify-between gap-6 border-b border-gray-200 pb-4">
            <div>
                <h1 class="text-xl font-bold tracking-wide text-gray-900">{{ __('EMPLOYEE DETAILS FORM') }}</h1>
                <p class="mt-1 text-gray-600">{{ $e->company?->establishment_name }} · {{ __('Employee id') }} <span class="font-mono">{{ $e->employee_code }}</span></p>
                <p class="mt-1"><x-status-badge :active="$e->status === \App\Enums\EmployeeStatus::Active" />
                    @if ($e->separation_date)<span class="ms-2 text-xs text-orange-700">{{ __('Separation date: :date', ['date' => $date($e->separation_date)]) }}</span>@endif
                </p>
            </div>
            <figure class="text-center">
                <x-employee-photo :employee="$e" class="h-32 w-28 !rounded-md text-lg" />
                <figcaption class="mt-1 text-xs text-gray-500">{{ __('Passport Size Photo') }}</figcaption>
            </figure>
        </header>

        @include('employees._details-section', ['title' => '1. '.__('Basic Information'), 'rows' => [
            __('Full Name') => $e->full_name,
            __('Date of Birth') => $date($e->date_of_birth),
            __('Email') => $e->email,
            __('Phone') => $e->phone,
            __('Alternate Phone') => $e->alternate_phone,
            __('Gender') => $e->gender?->label(),
            __('Marital Status') => config('employee.marital_statuses')[$e->marital_status] ?? '',
            __('Religion') => $e->religion,
            __('Caste') => $e->caste,
            __('No. of Kids') => $e->no_of_kids,
            __('Country') => $e->country,
        ]])

        @include('employees._details-section', ['title' => '2. '.__('Employment Information'), 'rows' => [
            __('Company Name') => $e->company?->establishment_name,
            __('Reporting Officer') => $e->reportingOfficer ? "{$e->reportingOfficer->employee_code} - {$e->reportingOfficer->full_name}" : '',
            __('Shift') => $e->shift?->name,
            __('Joining Date') => $date($e->date_of_joining),
            __('Department') => $e->department?->name,
            __('Sub Department') => $e->subDepartment?->name,
            __('Designation') => $e->position?->name,
            __('Employee Type') => $e->employment_type?->label(),
            __('Pay Frequency') => config('employee.pay_frequencies')[$e->pay_frequency] ?? '',
            __('Hourly Rate') => $e->hourly_rate,
            __('Daily Rate') => $e->daily_rate,
            __('Working Place') => config('employee.working_places')[$e->working_place] ?? '',
            __('Working on hazardous process') => $yesNo($e->works_on_hazardous_process),
            __('Can Punch In Hybrid Mode') => $yesNo($e->can_punch_in_hybrid_mode),
            __('Weekly Leave Day') => config('employee.weekdays')[$e->weekly_leave_day] ?? '',
            __('Nfc card no') => $e->nfc_card_no,
        ]])

        @include('employees._details-section', ['title' => '3. '.__('Salary Information'), 'rows' => $salary->mapWithKeys(fn ($row, $key) => [
            ($labels[$key] ?? $key) => $key === 'bonus' ? rtrim(rtrim($row->monthly, '0'), '.').'%' : number_format((float) $row->monthly, 2).' / '.__('month'),
        ])->all() ?: [__('Salary structure') => __('Not entered')]])

        @foreach (['present' => '4. '.__('Present Address'), 'permanent' => '5. '.__('Permanent Address')] as $type => $title)
            @php($address = $e->address($type))
            @include('employees._details-section', ['title' => $title, 'rows' => [
                __('Address Line 1') => $address?->address_line_1, __('Address Line 2') => $address?->address_line_2, __('Village') => $address?->village,
                __('District') => $address?->district, __('Taluka') => $address?->taluka, __('State') => $address?->state,
            ]])
        @endforeach

        @include('employees._details-section', ['title' => '6. '.__('Document Information'), 'rows' => [
            __('Aadhar No.') => $masked($e->aadhar_card_no),
            __('PAN No.') => $e->pan_card_no,
            __('Passport No.') => $e->passport_no,
            __('Driving License No.') => $e->driving_license_no,
        ]])
        <p class="-mt-3 mb-5 flex flex-wrap gap-3 text-xs print:hidden">
            @foreach (['aadhar_attachment' => 'Aadhar (attachment)', 'pan_attachment' => 'PAN (attachment)', 'bank_passbook' => 'Bank Passbook (attachment)'] as $file => $label)
                @if ($e->{$file.'_path'})
                    <a href="{{ route('employees.file', [$e, $file]) }}" target="_blank" class="text-indigo-600 hover:underline">{{ __($label) }}</a>
                @endif
            @endforeach
        </p>

        @include('employees._details-section', ['title' => '7. '.__('Bank Information'), 'rows' => [
            __('Bank Name') => $e->bank_name, __('Branch') => $e->branch_address, __('Account No.') => $masked($e->account_number),
            __('IFSC Code') => $e->ifsc_code, __('MICR Code') => $e->micr_code, __('UAN Code') => $e->uan_code,
            __('Name as per UAN') => $e->name_as_per_uan, __('IP Number') => $e->ip_number, __('Name as per ESIC') => $e->name_as_per_esic,
        ]])

        @include('employees._details-section', ['title' => '8. '.__('Medical Information'), 'rows' => [
            __('Blood Group') => $e->blood_group, __('Health Condition') => $e->health_condition,
            __('Is Disabled') => $yesNo($e->is_disabled), __('Disability Description') => $e->disability_description,
        ]])

        <section class="mb-5 break-inside-avoid">
            <h2 class="mb-2 border-b border-gray-200 pb-1 font-semibold text-gray-900">9. {{ __('Qualification Details') }}</h2>
            <table class="w-full text-left">
                <thead class="text-gray-600"><tr><th class="py-1">{{ __('Qualification') }}</th><th>{{ __('School/College Name') }}</th><th>{{ __('Tenure') }}</th><th>{{ __('Year of Passing') }}</th><th>{{ __('Percentage') }}</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach (config('employee.qualification_levels') as $level => $label)
                        @php($q = $e->qualifications->firstWhere('level', $level))
                        <tr><td class="py-1">{{ $label }}</td><td>{{ $q?->school }}</td><td>{{ $q?->tenure }}</td><td>{{ $q?->passing_year }}</td><td>{{ $q?->percentage }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-1 text-gray-600">{{ __('Job status') }}: {{ config('employee.job_statuses')[$e->job_status] ?? '' }}</p>
        </section>

        <section class="mb-5 break-inside-avoid">
            <h2 class="mb-2 border-b border-gray-200 pb-1 font-semibold text-gray-900">10. {{ __('Experience Details') }}</h2>
            <table class="w-full text-left">
                <thead class="text-gray-600"><tr><th class="py-1">{{ __('Company Name') }}</th><th>{{ __('Designation') }}</th><th>{{ __('Salary') }}</th><th>{{ __('Total Years Worked') }}</th><th>{{ __('Reason of Leaving') }}</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($e->experiences as $x)
                        <tr><td class="py-1">{{ $x->company_name }}</td><td>{{ $x->designation }}</td><td>{{ $x->salary }}</td><td>{{ $x->total_years_worked }}</td><td>{{ $x->reason_of_leaving }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-1 text-gray-500">{{ __('None') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <p class="mt-1 text-gray-600">{{ __('Date of exit from the previous organisation') }}: {{ $date($e->date_of_exit_prev_org) }}</p>
        </section>

        <section class="mb-5 break-inside-avoid">
            <h2 class="mb-2 border-b border-gray-200 pb-1 font-semibold text-gray-900">11. {{ __('Family Details') }}</h2>
            <table class="w-full text-left">
                <thead class="text-gray-600"><tr><th class="py-1">{{ __('Relation') }}</th><th>{{ __('Full Name') }}</th><th>{{ __('Date of Birth') }}</th><th>{{ __('Aadhar No') }}</th><th>{{ __('Is Nominee') }}</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach (config('employee.family_relations') as $relation => $label)
                        @php($m = $e->familyMembers->firstWhere('relation', $relation))
                        <tr><td class="py-1">{{ $label }}</td><td>{{ $m?->full_name }}</td><td>{{ $date($m?->date_of_birth) }}</td><td>{{ $masked($m?->aadhar_card_no) }}</td><td>{{ $m ? $yesNo($m->is_nominee) : '' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        @foreach (['nominee_current' => '11(a). '.__('Nominee Present Address'), 'nominee_permanent' => '11(b). '.__('Nominee Permanent Address')] as $type => $title)
            @php($address = $e->address($type))
            @include('employees._details-section', ['title' => $title, 'rows' => [
                __('Address Line 1') => $address?->address_line_1, __('Address Line 2') => $address?->address_line_2, __('Village') => $address?->village,
                __('District') => $address?->district, __('Taluka') => $address?->taluka, __('State') => $address?->state,
            ]])
        @endforeach
    </article>
</x-app-layout>
