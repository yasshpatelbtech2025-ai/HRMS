@php $m = fn ($n) => $n === null ? '-' : number_format((float) $n, 2); @endphp
<h1>Form - B - Register Of Wages / Register Of Overtime</h1>
<table class="info" style="width: auto;">
    <tr><td>Name of Establishment</td><td>:</td><td>{{ $company->establishment_name }}</td></tr>
    <tr><td>Name of Contractor / Firm</td><td>:</td><td>{{ $contractor }}</td></tr>
    <tr><td>Wage-period from</td><td>:</td><td>{{ $month->toDateString() }} &nbsp;to&nbsp; {{ $month->endOfMonth()->toDateString() }}</td></tr>
    <tr><td>Place</td><td>:</td><td>{{ $company->district }}</td></tr>
</table>
<table class="reg">
    <tr>
        <th rowspan="2">SL No.</th><th rowspan="2">Name of the employee</th><th rowspan="2">Father's/ Husband's Name</th><th rowspan="2">Designation</th>
        <th>Minimum rates of payable</th><th>Rate of wages actually paid</th>
        <th rowspan="2">Total attendance /Units of work done</th><th rowspan="2">Overtime worked</th><th rowspan="2">Overtime Payable</th>
        <th rowspan="2">Gross wages payable</th><th rowspan="2">Deduction</th><th rowspan="2">Other deduction</th><th rowspan="2">Total deduction</th>
        <th rowspan="2">Wages paid</th><th rowspan="2">Date of payment</th><th rowspan="2">Signature or Thumb-impression of employee</th>
    </tr>
    <tr><th>Daily Rate</th><th>Month Salary</th></tr>
    @forelse ($employees as $e)
        @php $w = $wages[$e->id]; $basic = (float) ($e->salaryElements->firstWhere('element', 'basic')->monthly ?? 0); @endphp
        <tr>
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $e->full_name }}</td>
            <td>{{ \App\Services\Compliance\ComplianceForms::guardian($e) }}</td>
            <td>{{ $e->position?->name }}</td>
            <td class="money">{{ $e->pay_frequency === 'daily' ? $m($e->daily_rate) : '-' }}</td>
            <td class="money">{{ $e->pay_frequency === 'monthly' && $basic ? $m($basic) : '-' }}</td>
            <td class="num">{{ $w ? $w['days'] + 0 : '-' }}</td>
            <td class="num">{{ $w ? $w['ot_hours'] + 0 : '-' }}</td>
            <td class="money">{{ $w ? $m($w['overtime']) : '-' }}</td>
            <td class="money">{{ $w ? $m($w['gross']) : '-' }}</td>
            <td class="money">{{ $w ? $m($w['statutory']) : '-' }}</td>
            <td class="money">{{ $w ? $m($w['other_deductions']) : '-' }}</td>
            <td class="money">{{ $w ? $m($w['deductions']) : '-' }}</td>
            <td class="money">{{ $w ? $m($w['net']) : '-' }}</td>
            <td class="nw">{{ $w ? $sheet?->paid_date?->format('d-m-Y') : '' }}</td>
            <td></td>
        </tr>
    @empty
        <tr><td colspan="16" class="empty">{{ __('No employees for this month.') }}</td></tr>
    @endforelse
</table>
@unless ($sheet)
    <p class="small">{{ __('Salary for :month has not been generated yet (Payroll › Salary Generate), so wage columns are empty.', ['month' => $month->format('F Y')]) }}</p>
@endunless
