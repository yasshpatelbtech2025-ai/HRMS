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

    <a href="{{ route('employees.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm text-ink-2 hover:text-ink print:hidden"><x-icon name="chevron-left" class="size-4" /> {{ __('Back to employee list') }}</a>

    {{-- Profile header (screen only; the printed form below carries its own header). --}}
    <section class="card mb-6 p-5 sm:p-6 print:hidden">
        <div class="flex flex-wrap items-start gap-5">
            <x-employee-photo :employee="$e" class="size-20 shrink-0 text-xl" />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold text-ink">{{ $e->full_name }}</h1>
                    <x-status-badge :active="$e->status === \App\Enums\EmployeeStatus::Active" />
                    @if ($e->separation_date)<span class="text-xs text-warn">{{ __('Separation date: :date', ['date' => $date($e->separation_date)]) }}</span>@endif
                </div>
                <p class="mt-0.5 text-sm text-ink-2">
                    {{ collect([$e->position?->name, $e->subDepartment?->name ?? $e->department?->name, $e->company?->establishment_name])->filter()->implode(' · ') }}
                    <span class="font-mono text-ink-3">· {{ $e->employee_code }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="secondary" type="button" onclick="window.print()">{{ __('Print') }}</x-ui.button>
                <x-ui.button :href="route('employees.edit', $e)" icon="pencil">{{ __('Edit') }}</x-ui.button>
            </div>
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 border-t border-line pt-4 text-sm sm:grid-cols-3 lg:grid-cols-5">
            @foreach ([
                __('Phone') => $e->phone,
                __('Email') => $e->email,
                __('Joined') => $e->date_of_joining ? $e->date_of_joining->format('d M Y').' · '.$e->date_of_joining->diffForHumans(null, true) : null,
                __('Shift') => $e->shift?->name,
                __('Employee Type') => $e->employment_type?->label(),
            ] as $label => $value)
                <div class="min-w-0">
                    <dt class="text-xs text-ink-3">{{ $label }}</dt>
                    <dd class="truncate font-medium text-ink">{{ $value ?: '—' }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4 text-sm">
            <a href="{{ route('attendance.employee-report', ['employee' => $e->uuid, 'month' => now()->format('Y-m')]) }}" class="inline-flex h-control-sm items-center gap-1.5 rounded-control border border-line-strong px-3 text-13 font-medium text-ink hover:bg-sunken"><x-icon name="clock" class="size-4" /> {{ __('Attendance this month') }}</a>
            <a href="{{ route('payroll.revisions', ['employee' => $e->uuid]) }}" class="inline-flex h-control-sm items-center gap-1.5 rounded-control border border-line-strong px-3 text-13 font-medium text-ink hover:bg-sunken"><x-icon name="wallet" class="size-4" /> {{ __('Salary revisions') }}</a>
        </div>
    </section>

    <h2 class="mb-3 text-[15px] font-semibold text-ink print:hidden">{{ __('Full details') }}</h2>

    <article class="card p-5 text-sm sm:p-6 print:max-w-none print:border-0 print:p-0 print:shadow-none print:ring-0">
        <header class="mb-6 hidden items-start justify-between gap-6 border-b border-gray-200 pb-4 print:flex">
            <div>
                <h1 class="text-xl font-bold tracking-wide text-gray-900">{{ __('EMPLOYEE DETAILS FORM') }}</h1>
                <p class="mt-1 text-gray-600">{{ $e->company?->establishment_name }} · {{ __('Employee id') }} <span class="font-mono">{{ $e->employee_code }}</span></p>
                <p class="mt-1"><x-status-badge :active="$e->status === \App\Enums\EmployeeStatus::Active" />
                    @if ($e->separation_date)<span class="ms-2 text-xs text-warn">{{ __('Separation date: :date', ['date' => $date($e->separation_date)]) }}</span>@endif
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
            @php $address = $e->address($type); @endphp
            @include('employees._details-section', ['title' => $title, 'rows' => [
                __('Address Line 1') => $address?->address_line_1, __('Address Line 2') => $address?->address_line_2, __('City') => $address?->city,
                __('Village') => $address?->village, __('Taluka') => $address?->taluka, __('District') => $address?->district,
                __('State') => $address?->state, __('Country') => $address?->country,
            ]])
        @endforeach

        @include('employees._details-section', ['title' => '6. '.__('Statutory & Document Information'), 'rows' => [
            __('Aadhar No.') => $masked($e->aadhar_card_no),
            __('PAN No.') => $e->pan_card_no,
            __('UAN No.') => $e->uan_code,
            __('ESIC No.') => $e->ip_number,
            __('Nominee Name') => $e->nominee_name,
            __('Nominee Relationship') => $e->nominee_relationship,
            __('Passport No.') => $e->passport_no,
            __('Driving License No.') => $e->driving_license_no,
        ]])
        <p class="-mt-3 mb-5 flex flex-wrap gap-3 text-xs print:hidden">
            @foreach (['aadhar_attachment' => 'Aadhar (attachment)', 'pan_attachment' => 'PAN (attachment)', 'uan_document' => 'UAN Document (attachment)', 'nominee_document' => 'Nominee Document (attachment)', 'bank_passbook' => 'Bank Passbook (attachment)'] as $file => $label)
                @if ($e->{$file.'_path'})
                    <a href="{{ route('employees.file', [$e, $file]) }}" target="_blank" class="text-brand-600 hover:underline">{{ __($label) }}</a>
                @endif
            @endforeach
        </p>

        @include('employees._details-section', ['title' => '7. '.__('Bank Information'), 'rows' => [
            __('Bank Name') => $e->bank_name, __('Branch') => $e->branch_address, __('Account No.') => $masked($e->account_number),
            __('IFSC Code') => $e->ifsc_code, __('MICR Code') => $e->micr_code,
        ]])

        @include('employees._details-section', ['title' => '8. '.__('Medical Information'), 'rows' => [
            __('Blood Group') => $e->blood_group, __('Health Condition') => $e->health_condition,
            __('Is Disabled') => $yesNo($e->is_disabled), __('Disability Description') => $e->disability_description,
        ]])

        <section class="mb-5 break-inside-avoid">
            <h2 class="mb-3 border-b border-line pb-1.5 font-semibold text-ink">9. {{ __('Qualification Details') }}</h2>
            <div class="overflow-x-auto print:overflow-visible"><table class="w-full text-left">
                <thead class="text-gray-600"><tr><th class="py-1">{{ __('Qualification') }}</th><th>{{ __('School/College Name') }}</th><th>{{ __('Tenure') }}</th><th>{{ __('Year of Passing') }}</th><th>{{ __('Percentage') }}</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach (config('employee.qualification_levels') as $level => $label)
                        @php $q = $e->qualifications->firstWhere('level', $level); @endphp
                        <tr><td class="py-1">{{ $label }}</td><td>{{ $q?->school }}</td><td>{{ $q?->tenure }}</td><td>{{ $q?->passing_year }}</td><td>{{ $q?->percentage }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
            <p class="mt-1 text-gray-600">{{ __('Job status') }}: {{ config('employee.job_statuses')[$e->job_status] ?? '' }}</p>
        </section>

        <section class="mb-5 break-inside-avoid">
            <h2 class="mb-3 border-b border-line pb-1.5 font-semibold text-ink">10. {{ __('Experience Details') }}</h2>
            <div class="overflow-x-auto print:overflow-visible"><table class="w-full text-left">
                <thead class="text-gray-600"><tr><th class="py-1">{{ __('Company Name') }}</th><th>{{ __('Designation') }}</th><th>{{ __('Salary') }}</th><th>{{ __('Total Years Worked') }}</th><th>{{ __('Reason of Leaving') }}</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($e->experiences as $x)
                        <tr><td class="py-1">{{ $x->company_name }}</td><td>{{ $x->designation }}</td><td>{{ $x->salary }}</td><td>{{ $x->total_years_worked }}</td><td>{{ $x->reason_of_leaving }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-1 text-gray-500">{{ __('None') }}</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <p class="mt-1 text-gray-600">{{ __('Date of exit from the previous organisation') }}: {{ $date($e->date_of_exit_prev_org) }}</p>
        </section>

        <section class="mb-5 break-inside-avoid">
            <h2 class="mb-3 border-b border-line pb-1.5 font-semibold text-ink">11. {{ __('Family Details') }}</h2>
            <div class="overflow-x-auto print:overflow-visible"><table class="w-full text-left">
                <thead class="text-gray-600"><tr><th class="py-1">{{ __('Relation') }}</th><th>{{ __('Full Name') }}</th><th>{{ __('Date of Birth') }}</th><th>{{ __('Aadhar No') }}</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @foreach (config('employee.family_relations') as $relation => $label)
                        @php $m = $e->familyMembers->firstWhere('relation', $relation); @endphp
                        <tr><td class="py-1">{{ $label }}</td><td>{{ $m?->full_name }}</td><td>{{ $date($m?->date_of_birth) }}</td><td>{{ $masked($m?->aadhar_card_no) }}</td></tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>

        @foreach (['nominee_current' => '11(a). '.__('Nominee Present Address'), 'nominee_permanent' => '11(b). '.__('Nominee Permanent Address')] as $type => $title)
            @php $address = $e->address($type); @endphp
            @include('employees._details-section', ['title' => $title, 'rows' => [
                __('Address Line 1') => $address?->address_line_1, __('Address Line 2') => $address?->address_line_2, __('City') => $address?->city,
                __('Village') => $address?->village, __('Taluka') => $address?->taluka, __('District') => $address?->district,
                __('State') => $address?->state, __('Country') => $address?->country,
            ]])
        @endforeach
    </article>
</x-app-layout>
