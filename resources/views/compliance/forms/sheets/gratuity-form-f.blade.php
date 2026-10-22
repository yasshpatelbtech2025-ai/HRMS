@if (! $employee)
    <p class="empty">{{ __('Select an employee.') }}</p>
@else
@php
    $period = 'employee:'.$employee->id;
    // The nominee from the employee form's Statutory Info; their age comes from Family Detail when the names match.
    $nomineeMember = $employee->nominee_name ? $employee->familyMembers->first(fn ($m) => mb_strtolower(trim((string) $m->full_name)) === mb_strtolower(trim($employee->nominee_name))) : null;
    $nomineeAddress = \App\Services\Compliance\ComplianceForms::address($employee->address('nominee_permanent') ?? $employee->address('permanent'));
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<h1>Form F</h1>
<p class="sub">[See sub-rule (1) of rule 6]</p>
<p class="caption">NOMINATION</p>
<p>To.<br>{{ $company->establishment_name }}.</p>
<table class="info">
    <tr><td style="width:4%">1.</td><td>Shri/Shrimati/Kumari <strong>{{ $employee->full_name }}</strong> whose particulars are given in the statement below, hereby nominate the person(s) mentioned below to receive the gratuity payable after my death as also gratuity standing to my credit in the event of my death before that amount has become payable, or having become payable has not been paid and direct that the said amount of gratuity shall be paid in proportion indicated against the name(s) of the nominee(s).</td></tr>
    <tr><td>2.</td><td>I hereby certify that the person(s) mentioned is/are member(s) of my family within the meaning of Cl. (h) of Sec. 2 of the Payment of Gratuity Act, 1972.</td></tr>
    <tr><td>3.</td><td>I hereby declare that I have no family within the meaning of Cl. (h) of Sec.2 of the said Act.</td></tr>
    <tr><td>4.</td><td>(a) My father/mother/parents is/are not dependent on me. (b) My husband's father/mother/parents is/are not dependent on my husband.</td></tr>
    <tr><td>5.</td><td>I have excluded my husband from my family by a notice dated the @include('compliance.forms.sheets._value', ['key' => 0, 'type' => 'date']) to the controlling authority in terms of the proviso to Cl. (h) of Sec. 2 of the said Act.</td></tr>
    <tr><td>6.</td><td>Nomination made herein invalidates my previous nomination.</td></tr>
</table>
<p class="caption">NOMINEE(S)</p>
<table class="reg">
    <tr><th>Name in full with full address of nominee(s)</th><th>Relationship with the employee</th><th>Age of nominee</th><th>Proportion by which the gratuity will be shared</th></tr>
    <tr class="colno">@foreach (range(1, 4) as $n)<td>{{ $n }}</td>@endforeach</tr>
    @if ($employee->nominee_name)
        <tr>
            <td>{{ $employee->nominee_name }}{{ $nomineeAddress ? ', '.$nomineeAddress : '' }}</td>
            <td>{{ $employee->nominee_relationship }}</td>
            <td class="num">{{ $nomineeMember?->date_of_birth?->age }}</td>
            <td class="num">100%</td>
        </tr>
    @else
        <tr><td colspan="4" class="empty">{{ __('No nominee is entered in this employee\'s Statutory Info.') }}</td></tr>
    @endif
</table>
<p class="caption">STATEMENT</p>
<table class="info">
    @foreach ([
        'Name of employee in full.' => $employee->full_name,
        'Sex.' => $employee->gender?->label(),
        // Part of the prescribed form, so the line stays; religion is no longer collected.
        'Religion.' => '',
        'Whether unmarried/married/widow/widower.' => config('employee.marital_statuses')[$employee->marital_status] ?? '',
        'Department/Branch/Section where employed.' => $employee->subDepartment?->name ?? $employee->department?->name,
        'Post held with Ticket No. or Serial No., if any.' => $employee->position?->name.' / '.$employee->employee_code,
        'Date of appointment.' => $employee->date_of_joining?->format('d-m-Y'),
        'Permanent address.' => \App\Services\Compliance\ComplianceForms::address($employee->address('permanent')),
    ] as $label => $value)
        <tr><td style="width:4%">{{ $loop->iteration }}.</td><td class="label">{{ $label }}</td><td class="sep">:</td><td>{{ $value }}</td></tr>
    @endforeach
</table>
<table class="info" style="margin-top: 10px;">
    <tr><td>Place @include('compliance.forms.sheets._value', ['key' => 2])<br>Date @include('compliance.forms.sheets._value', ['key' => 3, 'type' => 'date'])</td><td style="text-align: right;">Signature/Thumb-impression of the employee.</td></tr>
</table>
<p class="caption">DECLARATION BY WITNESSES</p>
<p>Nomination signed/thumb-impressed before me.</p>
<table class="info">
    <tr><td>Name in full and full address of witnesses:</td><td>Signature of witnesses:</td></tr>
    <tr><td>1. @include('compliance.forms.sheets._value', ['key' => 4])</td><td>1.</td></tr>
    <tr><td>2. @include('compliance.forms.sheets._value', ['key' => 5])</td><td>2.</td></tr>
    <tr><td>Place @include('compliance.forms.sheets._value', ['key' => 6])<br>Date @include('compliance.forms.sheets._value', ['key' => 7, 'type' => 'date'])</td><td></td></tr>
</table>
<p class="caption">CERTIFICATE BY THE EMPLOYER</p>
<p>Certificate that the particulars of the above nomination have been verified and recorded in this establishment.</p>
<p>Employer's Reference No., if any. @include('compliance.forms.sheets._value', ['key' => 11])</p>
<table class="info"><tr><td>Date @include('compliance.forms.sheets._value', ['key' => 8, 'type' => 'date'])</td><td style="text-align: right;">Signature of the employer/ officer authorised.<br>Designation. @include('compliance.forms.sheets._value', ['key' => 12])<br>{{ $company->establishment_name }}</td></tr></table>
<p class="caption">ACKNOWLEDGMENT BY THE EMPLOYEE</p>
<p>Received the duplicate copy of nomination in Form F filed by me and duly certified by the employer.</p>
<table class="info"><tr><td>Date @include('compliance.forms.sheets._value', ['key' => 9, 'type' => 'date'])</td><td style="text-align: right;">Signature of the employee.</td></tr></table>
@include('compliance.forms.sheets._save', ['period' => $period])
@unless ($printing)
</form>
@endunless
@endif
