<p class="sub">[ Contract Labour (Regulation &amp; Abolition) Act 1970 - Rule 76 ]</p>
<h1>FORM XIV</h1>
<p class="sub">[See Rule 76]</p>
<p class="caption">Employment Card</p>
@if (! $employee)
    <p class="empty">{{ __('Select an employee.') }}</p>
@else
    @php
        $rate = match ($employee->pay_frequency) {
            'daily' => $employee->daily_rate,
            'hourly' => $employee->hourly_rate,
            default => $employee->salaryElements->firstWhere('element', 'basic')?->monthly,
        };
    @endphp
    <table class="reg" style="margin-bottom: 14px;">
        <tr>
            <td style="width: 50%;">Name and address of Contractor<br><strong>{{ $contractor }}</strong></td>
            <td>Name and address of Establishment in under which contract is carried on<br><strong>{{ $company->establishment_name }}</strong>{{ $address ? ', '.$address : '' }}</td>
        </tr>
        <tr>
            <td>Nature of work and location of work<br><strong>{{ $company->district ?: '-' }}</strong></td>
            <td>Name and address of Principal Employer<br><strong>{{ $company->establishment_name }}</strong></td>
        </tr>
    </table>
    <table class="reg">
        @foreach ([
            'Name of workmen' => $employee->full_name,
            'Sl. No. in the register of workmen employed' => $employee->employee_code,
            'Nature of employment/Designation' => $employee->position?->name,
            'Wages rate (with particular of unit, in of piece-work)' => $rate ? number_format((float) $rate, 2) : '-',
            'Wage period' => config('employee.pay_frequencies')[$employee->pay_frequency] ?? '-',
            'Tenure of employment' => ($employee->date_of_joining?->format('d/m/Y') ?? '-').' to '.($employee->separation_date?->format('d/m/Y') ?? 'Till Date'),
            'Remarks' => '-',
        ] as $label => $value)
            <tr><td class="num" style="width: 5%;">{{ $loop->iteration }}</td><td style="width: 50%;">{{ $label }}</td><td>{{ $value }}</td></tr>
        @endforeach
    </table>
    <p style="text-align: right; margin-top: 40px;">Signature of Contractor</p>
@endif
