@php $period = $startYear.'-'.($startYear + 1); @endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<h1>FORM D</h1>
<p class="sub">(See rule 5)</p>
<p class="caption">Annual Return-Bonus paid to employees for the accounting year ending on the {{ $yearEnd->format('jS F Y') }}</p>
<table class="info">
    <tr><td style="width:4%">1.</td><td class="label">Name of the establishment and is complete postal address</td><td class="sep">:</td><td>{{ $company->establishment_name }}{{ $address ? ', '.$address : '' }}</td></tr>
    <tr><td>2.</td><td>Nature of the Industry</td><td>:</td><td></td></tr>
    <tr><td>3.</td><td>Name of the Employer</td><td>:</td><td>{{ $contractor }}</td></tr>
    <tr><td>4.</td><td>Total Number of employees</td><td>:</td><td>{{ $totalEmployees }}</td></tr>
    <tr><td>5.</td><td>Number of employees benefited by bonus payments</td><td>:</td><td>{{ $benefited }}</td></tr>
</table>
<table class="reg">
    <tr>
        <th>Total amount payables as bonus under Sec. 10 or 11 of the Payment of Bonus Act, 1965 as the case may be</th>
        <th>Settlement, if any reached under sec. 18 (1) or 12 (3) of the Industrial Disputes Act. 1947 with date</th>
        <th>Percentage of bonus declared to be paid</th><th>Total amount of bonus actually paid</th><th>Date on which payment made</th>
        <th>Whether bonus has been paid to all the employees. If not, reason for nonpayment</th><th>Remarks</th>
    </tr>
    <tr class="colno">@foreach (range(1, 7) as $n)<td>{{ $n }}</td>@endforeach</tr>
    <tr>
        <td>@include('compliance.forms.sheets._value', ['key' => 1])</td>
        <td>@include('compliance.forms.sheets._value', ['key' => 2])</td>
        <td>@include('compliance.forms.sheets._value', ['key' => 3])</td>
        <td>@include('compliance.forms.sheets._value', ['key' => 4])</td>
        <td>@include('compliance.forms.sheets._value', ['key' => 5, 'type' => 'date'])</td>
        <td>@include('compliance.forms.sheets._value', ['key' => 6])</td>
        <td>@include('compliance.forms.sheets._value', ['key' => 7])</td>
    </tr>
</table>
@if ($bonusPaid > 0 && ! $printing)
    <p class="small">{{ __('Payroll shows :amount paid as bonus (monthly with salary and the approved yearly bonus sheet) to :count employee(s) in this accounting year:paid.', ['amount' => number_format($bonusPaid, 2), 'count' => $benefited, 'paid' => $bonusPaidOn ? ' — yearly bonus paid on '.$bonusPaidOn->format('d-m-Y') : '']) }}</p>
@endif
<p style="text-align: right; margin-top: 40px;">Signature of the Employer or his agent.</p>
@include('compliance.forms.sheets._save', ['period' => $period])
@unless ($printing)
</form>
@endunless
