@php
    $columns = [
        'particular' => ['Act / Omission for which fine imposed', 'text'], 'date' => ['Date of offence', 'date'],
        'show_cause' => ['Whether workman showed cause againts fine', 'yesno'], 'heard_by' => ["Name of person in whose presence employee's explanation was heard", 'text'],
        'wages' => ['Wage periods and wages payable', 'text'], 'amount' => ['Amount of fine imposed', 'amount'],
        'recovered' => ['Date on which fine realised', 'date'], 'remarks' => ['Remarks', 'text'],
    ];
@endphp
<p class="sub">FORM XXI</p>
<p class="sub">[See Rule 78 (1) (a) (ii)]</p>
<h1>Register of Fines</h1>
@include('compliance.forms._establishment')
<table class="reg">
    <tr>
        <th>Sl No.</th><th>Name of workman</th><th>Father's / Husband's name</th><th>Designation / nature of employment</th>
        @foreach ($columns as [$label])<th>{{ $label }}</th>@endforeach
    </tr>
    <tr class="colno">@foreach (range(1, 12) as $n)<td>{{ $n }}</td>@endforeach</tr>
    @include('compliance.forms.sheets._entries', ['leading' => 'name-father-designation'])
    @if ($entries->isEmpty())
        <tr><td colspan="12" class="empty">No fines in {{ $month->format('F Y') }}</td></tr>
    @endif
</table>
@unless ($printing)
    @include('compliance.forms.sheets._entry-form', ['leading' => 'name-father-designation'])
@endunless
