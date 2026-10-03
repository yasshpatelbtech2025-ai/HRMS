@use('App\Services\Compliance\ComplianceForms', 'F')
@php
    $period = $startYear.'-'.($startYear + 1);
    $money = fn ($v) => number_format((float) $v, 2);
    $input = fn (string $key, string $type = 'number') => $printing
        ? ($type === 'date' ? ($values->value($key) ? \Carbon\Carbon::parse($values->value($key))->format('d-m-Y') : '') : ($values->value($key) !== null ? $money($values->value($key)) : ''))
        : view('compliance.forms.sheets._value', ['values' => $values, 'key' => $key, 'type' => $type, 'width' => $type === 'date' ? 130 : 90, 'printing' => false])->render();
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<h1>FORM C</h1>
<p class="sub">[See rule 4 (c)]</p>
<p class="caption">BONUS PAID TO EMPLOYEES FOR THE ACCOUNTING YEAR ENDING ON THE {{ strtoupper($yearEnd->format('jS F Y')) }}<br>
    <span style="font-weight: 400; font-size: 16px;">Accounting Period: {{ $yearStart->format('F Y') }} to {{ $yearEnd->format('F Y') }}</span></p>
<table class="info">
    <tr><td class="label">Name of the establishment</td><td class="sep">:-</td><td>{{ $company->establishment_name }}</td></tr>
    <tr><td class="label">No. of working days in the year</td><td class="sep">:-</td><td>{{ $workingDays }}</td></tr>
</table>
<table class="reg">
    <tr>
        <th rowspan="2">Sl. No.</th><th rowspan="2">Name of the employee</th><th rowspan="2">Father's name</th>
        <th rowspan="2">Whether he has completed 15 years of age at the beginning of the accounting year</th><th rowspan="2">Designation</th>
        <th rowspan="2">No. of days worked in the year</th><th rowspan="2">Total salary or wage in respect of the accounting year</th>
        <th rowspan="2">Amount of bonus payable under section 10 or section 11 as the case may be</th>
        <th rowspan="2">Puja bonus or other customary bonus during the accounting year</th><th rowspan="2">Interim bonus or bonus paid in advance</th>
        <th colspan="3">Deductions</th>
        <th rowspan="2">Net amount payable (Column 8 minus Column 12)</th><th rowspan="2">Amount actually paid</th><th rowspan="2">Date on which paid</th>
        <th rowspan="2">Signature/thumb impression of the employee</th>
    </tr>
    <tr>
        <th>Amount of income-tax deducted</th><th>Deduction on account of financial loss, if any, caused by misconduct of employee</th>
        <th>Total sum deducted under columns 9, 10, 10A and 11</th>
    </tr>
    <tr class="colno">@foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '10A', '11', '12', '13', '14', '15', '16'] as $col)<td>{{ $col }}</td>@endforeach</tr>
    @forelse ($rows as $row)
        @php
            $e = $row['employee'];
            $k = fn ($name) => $e->id.'_'.$name;
            $deducted = collect(['puja', 'interim', 'tax', 'loss'])->sum(fn ($name) => (float) $values->value($k($name)));
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $e->full_name }}</td>
            <td>{{ F::guardian($e) }}</td>
            <td>{{ $row['over15'] === null ? '' : ($row['over15'] ? 'Yes' : 'No') }}</td>
            <td>{{ $e->position?->name }}</td>
            <td>{{ $row['days'] + 0 }}</td>
            <td class="money">{{ $money($row['salary']) }}</td>
            <td class="money">{{ $money($row['payable']) }}@if ($row['rate'] && ! $printing)<br><span class="small" style="color: #555;">{{ $row['rate'] + 0 }}%</span>@endif</td>
            <td>{!! $input($k('puja')) !!}</td>
            <td>{!! $input($k('interim')) !!}</td>
            <td>{!! $input($k('tax')) !!}</td>
            <td>{!! $input($k('loss')) !!}</td>
            <td class="money">{{ $money($deducted) }}</td>
            <td class="money">{{ $money($row['payable'] - $deducted) }}</td>
            <td>{!! $input($k('paid')) !!}</td>
            <td class="nw">{!! $input($k('paid_on'), 'date') !!}</td>
            <td style="min-width: 80px;"></td>
        </tr>
    @empty
        <tr><td colspan="17" class="empty">No employees in this accounting year</td></tr>
    @endforelse
</table>
@unless ($printing)
    <p class="small" style="color: #555;">
        @if ($bonusSheet)
            Days worked, basic + DA and bonus come from the yearly bonus sheet for this year (Payroll › Bonus){{ $bonusSheet->isApproved() ? ', with the amount and date paid' : ', still a draft' }}.
        @else
        Days worked, salary (basic + DA) and bonus come from the payslips for {{ $yearStart->format('M Y') }} – {{ $yearEnd->format('M Y') }}. Bonus payable is the bonus paid monthly through payroll;
        when none was, it is the employee's Bonus % (8.33%, the statutory minimum, when none is set) of that salary. Columns 12 and 13 are worked out when you save.
        For the yearly bonus, work it out in Payroll › Bonus.
        @endif
    </p>
@endunless
@include('compliance.forms.sheets._save', ['period' => $period])
@unless ($printing)
</form>
@endunless
