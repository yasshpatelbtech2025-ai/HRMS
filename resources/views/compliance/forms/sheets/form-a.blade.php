@use('App\Services\Compliance\ComplianceForms', 'F')
@php
    $levels = array_keys(config('employee.qualification_levels'));
    $headings = ['Sl No.', 'Employee Code', 'Name', 'Gender', 'Father / Spouse', 'Date of Birth', 'Nationality', 'Education Level', 'Date of Joining', 'Designation',
        'Category Address', 'Type of Employment', 'Mobile', 'UAN', 'PAN', 'ESIC IP', 'LWF', 'AADHAR', 'Bank A/C Number', 'Bank', 'Branch(IFSC)', 'Present Address',
        'Permanent Address', 'Service Book No.', 'Date of Exit', 'Reason for Exit', 'Mark of Identification', 'Photo', 'Specimen Signature / Thumb Impression', 'Remarks'];
@endphp
<p class="sub">SCHEDULE</p>
<p class="sub">[See rule 2(1)]</p>
<h1>FORM A</h1>
<p class="caption">EMPLOYEE REGISTER<br>[Part-A: For all Establishments]</p>
<table class="info" style="width: auto;">
    <tr><td>Name of the Establishment</td><td>:</td><td>{{ $company->establishment_name }}</td></tr>
    <tr><td>Name of Owner / Contractor</td><td>:</td><td>{{ $contractor }}</td></tr>
    <tr><td>For the month of</td><td>:</td><td>{{ $month->format('M, Y') }}</td></tr>
</table>
<table class="reg">
    <tr>@foreach ($headings as $h)<th>{{ $h }}</th>@endforeach</tr>
    <tr class="colno">@foreach ($headings as $i => $h)<td>{{ $i + 1 }}</td>@endforeach</tr>
    @forelse ($employees as $e)
        @php
            $education = collect($levels)->reverse()->first(fn ($l) => $e->qualifications->firstWhere('level', $l));
        @endphp
        <tr>
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $e->employee_code }}</td>
            <td>{{ $e->full_name }}</td>
            <td>{{ $e->gender?->label() }}</td>
            <td>{{ F::guardian($e) }}</td>
            <td>{{ $e->date_of_birth?->format('d-m-Y') }}</td>
            <td>{{ strtoupper($e->country === 'India' ? 'Indian' : (string) $e->country) }}</td>
            <td>{{ $education ? config('employee.qualification_levels')[$education] : '' }}</td>
            <td>{{ $e->date_of_joining?->format('d-m-Y') }}</td>
            <td>{{ $e->position?->name }}</td>
            <td></td>
            <td>{{ $e->employment_type?->label() }}</td>
            <td>{{ $e->phone }}</td>
            <td>{{ $e->uan_code }}</td>
            <td>{{ $e->pan_card_no }}</td>
            <td>{{ $e->ip_number }}</td>
            <td></td>
            <td>{{ F::mask($e->aadhar_card_no) }}</td>
            <td>{{ F::mask($e->account_number) }}</td>
            <td>{{ $e->bank_name }}</td>
            <td>{{ $e->ifsc_code }}</td>
            <td>{{ F::address($e->address('present')) }}</td>
            <td>{{ F::address($e->address('permanent')) }}</td>
            <td></td>
            <td>{{ $e->separation_date?->format('d-m-Y') }}</td>
            <td></td>
            <td></td>
            <td>@if (! $printing && $e->photo_path)<img src="{{ route('employees.file', [$e, 'photo']) }}" alt="" style="height: 36px;">@endif</td>
            <td></td>
            <td></td>
        </tr>
    @empty
        <tr><td colspan="{{ count($headings) }}" class="empty">{{ __('No employees for this month.') }}</td></tr>
    @endforelse
</table>
<p class="small">{{ __('Aadhaar and bank account numbers are shown with only their last four digits.') }}</p>
