@php
    $columns = [
        'particular' => ['Particulars of damage or loss', 'text'], 'date' => ['Date of damage or loss', 'date'],
        'show_cause' => ['Whether workman showed cause againts deduction', 'yesno'], 'heard_by' => ["Name of person in whose presence employee's explanation was heard", 'text'],
        'amount' => ['Amount of deduction imposed', 'amount'], 'installments' => ['No. of instalments', 'number'], 'first' => ['Final instalment', 'text'],
        'last' => ['Last instalment', 'text'], 'remarks' => ['Remarks', 'text'],
    ];
@endphp
<p class="sub">Form XX</p>
<p class="sub">See Rule 78 (1) (a) (ii)</p>
<h1>Register of deductions for Damage or Loss</h1>
@include('compliance.forms._establishment')
<table class="reg">
    <tr>
        <th>Sl. No.</th><th>Name of workman</th><th>Father's/ Husband's name</th><th>Designation/ nature of employment</th>
        @foreach ($columns as [$label])<th>{{ $label }}</th>@endforeach
    </tr>
    <tr class="colno">@foreach (range(1, 13) as $n)<td>{{ $n }}</td>@endforeach</tr>
    @include('compliance.forms.sheets._entries', ['leading' => 'name-father-designation'])
    @if ($entries->isEmpty())
        <tr><td colspan="13" class="empty">No deductions for damage or loss in {{ $month->format('F Y') }}</td></tr>
    @endif
</table>
@unless ($printing)
    @include('compliance.forms.sheets._entry-form', ['leading' => 'name-father-designation'])
@endunless
