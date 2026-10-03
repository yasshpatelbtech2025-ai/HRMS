@use('App\Services\Compliance\ComplianceForms', 'F')
@php
    $e = $employee;
    $rows = $e?->examinations->sortBy('examined_on') ?? collect();
    $date = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d-m-Y') : '';
    $value = fn ($v) => '<span class="dots">'.e($v ?? '').'</span>';
@endphp
<div class="cert full">
    <h1>FORM NO. 32</h1>
    <p class="sub">(Prescribed under Rule 68-T and 102)</p>
    <h1>Health Register</h1>
    <br>
    <table class="fields" style="max-width: 760px;">
        <tr><td class="no">1.</td><td class="label">Serial Number in the Register of adult Workers</td><td class="sep">:</td><td>{!! $value($e?->employee_code) !!}</td></tr>
        <tr><td class="no">2.</td><td class="label">Name of Worker</td><td class="sep">:</td><td>{!! $value($e?->full_name) !!}</td></tr>
        <tr><td class="no">3.</td><td class="label">Sex</td><td class="sep">:</td><td>{!! $value($e?->gender?->label()) !!}</td></tr>
        <tr><td class="no">4.</td><td class="label">Date of birth / Age</td><td class="sep">:</td><td>{!! $value($e?->date_of_birth ? $e->date_of_birth->format('d-m-Y').' / '.F::age($e).' Years' : null) !!}</td></tr>
        <tr><td class="no">5.</td><td class="label">Date of Join</td><td class="sep">:</td><td>{!! $value($e?->date_of_joining?->format('d-m-Y')) !!}</td></tr>
        <tr><td class="no"></td><td class="label">Company Name</td><td class="sep">:</td><td>{!! $value($company->establishment_name) !!}</td></tr>
    </table>
    <div class="scroll-x">
    <table class="reg" style="margin-top: 16px;">
        <tr>
            <th rowspan="2">Department / Works</th><th rowspan="2">Name of hazardous process</th><th rowspan="2">Dangerous process / operation</th>
            <th rowspan="2">Nature of job or occupation</th><th rowspan="2">Raw materials, products or by-products likely to be exposed to</th>
            <th rowspan="2">Date of posting</th><th rowspan="2">Date of leaving / transfer to other process</th><th rowspan="2">Reasons for discharge / leaving or transfer</th>
            <th colspan="4">Medical examination and results thereof</th><th colspan="4">If declared unfit for work</th>
            <th rowspan="2">Signature with date of the factory Medical Officer / the Certifying Surgeon</th>
        </tr>
        <tr>
            <th>Date</th><th>Signs and symptoms observed during examination</th><th>Nature of tests &amp; results thereof</th><th>Result Fit / Unfit</th>
            <th>Period of temporary withdrawal from that work</th><th>Reasons for such withdrawal</th><th>Date of declaring him unfit for that work</th><th>Date of issuing fitness certificate</th>
        </tr>
        @forelse ($rows as $x)
            <tr>
                <td>{{ $e->subDepartment?->name }}</td>
                <td>{{ $x->detail('hazardous_process') ?? '-' }}</td>
                <td>{{ $x->detail('dangerous_operation') ?? '-' }}</td>
                <td>{{ $e->position?->name }}</td>
                <td>{{ $x->detail('raw_materials') }}</td>
                <td class="nw">{{ $date($x->detail('posted_on')) }}</td>
                <td class="nw">{{ $date($x->detail('left_on')) ?: 'N/A' }}</td>
                <td>{{ $x->detail('leaving_reason') ?? 'N/A' }}</td>
                <td class="nw">{{ $x->examined_on->format('d-m-Y') }}</td>
                <td>{{ $x->signs_symptoms ?? 'NIL' }}</td>
                <td>{{ $x->detail('tests') }}</td>
                <td>{{ $x->result ? ucfirst($x->result) : '' }}</td>
                <td>{{ $x->detail('withdrawal_period') ?? 'N/A' }}</td>
                <td>{{ $x->detail('withdrawal_reason') ?? 'N/A' }}</td>
                <td class="nw">{{ $date($x->detail('unfit_on')) ?: 'N/A' }}</td>
                <td class="nw">{{ $date($x->detail('certificate_on')) ?: 'N/A' }}</td>
                <td>{{ $x->certifying_surgeon }}</td>
            </tr>
        @empty
            <tr><td colspan="17" class="empty">No examinations recorded{{ $e ? ' for '.$e->full_name : '' }}</td></tr>
        @endforelse
    </table>
    </div>
    <div class="signs" style="font-weight: 600; text-decoration: underline;">
        <div>Sign Of Chief Medical Officer</div>
        <div>Sign Of Certifying Surgeon</div>
    </div>
</div>
