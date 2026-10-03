@php
    $period = $month->format('Y-m');
    $value = fn (string $key, string $type = 'text', int $width = 140, bool $inline = false) => view('compliance.forms.sheets._value', ['values' => $values, 'key' => $key, 'type' => $type, 'width' => $width, 'inline' => $inline, 'printing' => $printing])->render();
    $money = fn ($v) => number_format((float) $v, 2);
    $totalA = collect($slabs)->sum('tax');
    $totalB = (float) $values->value('arrears_tax');
    $interest = (float) $values->value('interest');
    $arrears = collect(['arrears_employees', 'arrears_rate', 'arrears_previous_rate', 'arrears_months', 'arrears_tax'])->contains(fn ($k) => $values->value($k) !== null);
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<div class="cert">
    <h1>FORM NO. 5</h1>
    <p style="text-align: center;">Registration Certificate No. {!! $printing ? ($values->value('registration_no') ?: '_______________________') : $value('registration_no', 'text', 240, true) !!}</p>
    <p class="sub" style="font-size: 16px;">Details of Employees during the month in respect of whom Tax is payable are as under {{ $month->format('F Y') }}</p>
    <p><strong>1.</strong> Details for tax calculation for the tax payable in respect of salaries for the month ending on : <strong>{{ $month->format('F Y') }}</strong></p>
    <table class="reg">
        <tr>
            <th>Employees whose monthly salaries or wages are</th><th>No. of employees</th>
            <th>No. of employees for whom no tax is payable / proviso (2) to Sec. (4)</th><th>No. of employees in respect of whom tax is payable</th>
            <th>Rate of tax</th><th>Amount of tax deducted</th>
        </tr>
        <tr class="colno">@foreach (range(1, 6) as $col)<td>{{ $col }}.</td>@endforeach</tr>
        @foreach ($slabs as $label => $s)
            <tr>
                <td style="text-align: left;">{{ $loop->iteration }}. {{ $label }}</td>
                <td>{{ $s['employees'] }}</td><td>{{ $s['exempt'] ?: '-' }}</td><td>{{ $s['taxed'] }}</td>
                <td>{{ $s['rate'] }}</td><td class="money">{{ $money($s['tax']) }}</td>
            </tr>
        @endforeach
        <tr><td style="text-align: left; font-weight: 700;">Total (A) Rs.</td><td></td><td></td><td></td><td></td><td class="money" style="font-weight: 700;">{{ $money($totalA) }}</td></tr>
    </table>
    @if (! $generated && ! $printing)
        <p class="small" style="color: #b45309;">No salary has been generated for {{ $month->format('F Y') }} yet (Payroll › Salary Generate), so the counts are 0.</p>
    @endif

    <p><strong>2.</strong> Details of employees in respect of whom tax is payable at the enhanced rate for previous period on account of arrears of salaries or wages paid during the month.</p>
    <table class="reg">
        <tr>
            <th>No. of employees liable to tax at enhanced rate</th><th>Rate payable on account of payment of arrears of salaries and wages</th>
            <th>Rate at which tax was paid previously</th><th>Difference of rate (Col. 2 minus Col. 3)</th>
            <th>No. of months for which arrears is held</th><th>Additional tax payable</th>
        </tr>
        <tr class="colno">@foreach (range(1, 6) as $col)<td>{{ $col }}.</td>@endforeach</tr>
        @if ($printing && ! $arrears)
            <tr><td colspan="6">NOT APPLICABLE</td></tr>
        @else
            <tr>
                <td>{!! $value('arrears_employees', 'number', 80) !!}</td><td>{!! $value('arrears_rate', 'number', 80) !!}</td><td>{!! $value('arrears_previous_rate', 'number', 80) !!}</td>
                <td>{{ $arrears ? (float) $values->value('arrears_rate') - (float) $values->value('arrears_previous_rate') : '' }}</td>
                <td>{!! $value('arrears_months', 'number', 80) !!}</td><td>{!! $value('arrears_tax', 'number', 100) !!}</td>
            </tr>
        @endif
        <tr><td colspan="5" style="text-align: left; font-weight: 700;">TOTAL (B) Rs.</td><td class="money" style="font-weight: 700;">{{ $money($totalB) }}</td></tr>
    </table>
    @unless ($printing)
        <p class="small" style="color: #555;">Leave section 2 blank when there are no arrears; it then prints as NOT APPLICABLE.</p>
    @endunless

    <table class="fields">
        <tr><td class="label">Total tax payable i.e. Total A + Total B</td><td class="sep">:</td><td><strong>{{ $money($totalA + $totalB) }}</strong></td></tr>
        <tr><td class="label">Add : Simple interest payable (if any) on the above amount at 2% p.m. or part thereof (vide Sec 9(2))</td><td class="sep">:</td><td>{!! $printing ? ($interest ? $money($interest) : '') : $value('interest', 'number') !!}</td></tr>
        <tr><td class="label"><strong>Total tax and interest payable</strong></td><td class="sep">:</td><td><strong>{{ $money($totalA + $totalB + $interest) }}</strong></td></tr>
        <tr><td class="label">Amount paid under Cheque/DD No.</td><td class="sep">:</td><td>{!! $value('cheque_no', 'text', 180, true) !!} &nbsp; Dated : {!! $value('cheque_date', 'date', 170, true) !!}</td></tr>
    </table>
    <p style="margin-top: 16px;">I certify that all the employees who are liable to pay the tax in my employment during the period of return have been covered by the foregoing particulars. I also certify that the necessary revision in the amount of the tax deductible from the salary or wages earned by them has been made where necessary.</p>
    <p>I, Mr. {!! $value('declarant', 'text', 260, true) !!} solemnly declare that the above statements are true to the best of my knowledge and belief.</p>
    <div class="signs">
        <div>Place : {!! $value('place', 'text', 200, true) !!}<br>Date : {!! $value('declared_on', 'date', 170, true) !!}</div>
        <div><br>Signature of the Employer</div>
    </div>
</div>
@unless ($printing)
    @include('compliance.forms.sheets._save', ['period' => $period])
</form>
@endunless
