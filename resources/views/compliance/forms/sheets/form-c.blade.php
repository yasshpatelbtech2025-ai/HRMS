@php
    $columns = [
        'type' => ['Recovery type (Damage/Loss/Fines/Advance/Loans/Absence)', 'recovery'], 'particular' => ['Perticular', 'text'],
        'date' => ['Date of Damage/Loss/Absence', 'date'], 'amount' => ['Amount', 'amount'], 'show_cause' => ['Whether show cause issued', 'yesno'],
        'heard_by' => ['Explanation heard in presence of', 'text'], 'installments' => ['Number of installments', 'number'], 'first' => ['First Month/Year', 'text'],
        'last' => ['Last Month/Year', 'text'], 'recovered' => ['Date of Complete Recovery', 'date'], 'remarks' => ['Remarks', 'text'],
    ];
@endphp
<h1>FORM C</h1>
<p class="caption">Format of Register of Loan/Recoveries/Damage/Loss/Fine/Advance/Adsence</p>
<table class="info">
    <tr><td class="label">Name of Establishment</td><td class="sep">:-</td><td>{{ $company->establishment_name }}</td><td class="label">Labour Identification No</td><td class="sep">:-</td><td>N/A</td></tr>
    <tr><td class="label">Name and Address of Principal Employer</td><td class="sep">:-</td><td>{{ $company->parent ? $company->establishment_name.($address ? ', '.$address : '') : '' }}</td><td class="label">Labour Identification No. of Principal Employer</td><td class="sep">:-</td><td>N/A</td></tr>
</table>
<table class="reg">
    <tr>
        <th>Sr. Number in Employee/Workmen/Worker Register</th><th>Name of Employee/Workmen/Worker</th>
        @foreach ($columns as [$label])<th>{{ $label }}</th>@endforeach
    </tr>
    <tr class="colno">@foreach (range(1, 13) as $n)<td>{{ $n }}</td>@endforeach</tr>
    @foreach ($rows as $row)
        <tr>
            <td class="num">{{ $row['employee']->employee_code }}</td>
            <td>{{ $row['employee']->full_name }}</td>
            <td>{{ $row['type'] }}</td><td>{{ $row['particular'] }}</td><td>{{ $row['date'] }}</td>
            <td class="money">{{ number_format($row['amount'], 2) }}</td><td>{{ $row['show_cause'] }}</td><td>{{ $row['heard_by'] }}</td>
            <td class="num">{{ $row['installments'] }}</td><td>{{ $row['first'] }}</td><td>{{ $row['last'] }}</td><td>{{ $row['recovered'] }}</td><td>{{ $row['remarks'] }}</td>
        </tr>
    @endforeach
    @include('compliance.forms.sheets._entries', ['leading' => 'sr-name'])
    @if ($rows->isEmpty() && $entries->isEmpty())
        <tr><td colspan="13" class="empty">No loan, fines or advances records for the month of {{ $month->format('F Y') }}</td></tr>
    @endif
</table>
@unless ($printing)
    <p class="small">{{ __('Advances and loan installments come from Payroll automatically; use Add Row for damage, loss, fines and absence.') }}</p>
    @include('compliance.forms.sheets._entry-form', ['leading' => 'sr-name'])
@endunless
