@use('App\Services\Compliance\ComplianceForms', 'F')
@php
    $e = $employee;
    $x = $examination;
    $dots = fn ($v, $width = 180) => '<span class="dots" style="min-width: '.$width.'px;">'.(e($v ?? '') ?: str_repeat('&nbsp;', 30)).'</span>';
@endphp
<div class="cert">
    <h1>FORM NO. 5</h1>
    <p class="sub">(Prescribed under Rule 15)</p>
    <p class="sub">Certificate of Fitness for Young Persons</p>
    <br>
    <div class="photo">Passport size photograph</div>
    <table class="fields">
        <tr><td class="no">1.</td><td class="label">Serial Number</td><td class="sep">:</td><td>{!! $dots($e?->employee_code) !!}</td></tr>
        <tr><td class="no">2.</td><td class="label">Name of person examined</td><td class="sep">:</td><td>{!! $dots($e?->full_name) !!}</td></tr>
        <tr><td class="no">3.</td><td class="label">Father's Name</td><td class="sep">:</td><td>{!! $dots($e ? F::guardian($e) : null) !!}</td></tr>
        <tr><td class="no">4.</td><td class="label">Sex</td><td class="sep">:</td><td>{!! $dots($e?->gender?->label()) !!}</td></tr>
        <tr><td class="no">5.</td><td class="label">Residence</td><td class="sep">:</td><td>{!! $dots($e ? F::residence($e) : null) !!}</td></tr>
        <tr><td class="no">6.</td><td class="label">Date of birth, if available</td><td class="sep">:</td><td>{!! $dots($e?->date_of_birth?->format('d-m-Y')) !!}</td></tr>
        <tr><td class="no">7.</td><td class="label">Referred by:&mdash;</td><td></td><td></td></tr>
        <tr><td></td><td class="label">(a) Name and address of the factory</td><td class="sep">:</td><td>{!! $dots($company->establishment_name.($address ? ', '.$address : '')) !!}</td></tr>
        <tr><td></td><td class="label">(b) Name of the manager</td><td class="sep">:</td><td>{!! $dots(null) !!}</td></tr>
        <tr><td class="no">8.</td><td class="label">Manufacturing process in which young person is proposed to be employed</td><td class="sep">:</td><td>{!! $dots($x?->detail('process') ?? $e?->subDepartment?->name) !!}</td></tr>
    </table>
    <p class="text" style="text-indent: 50px; margin-top: 16px;">
        I certify that I have personally examined the named person whose identification marks are {!! $dots($x?->detail('identification_marks'), 200) !!}
        and who is desirous of being employed in the above manufacturing process, and that his/her age, as nearly as can be ascertained
        from my examination, is {!! $dots($e ? F::age($e, $x?->examined_on) : null, 60) !!} years, and that he/she is
        {!! $dots($x?->result ? ucfirst($x->result) : 'fit/unfit', 80) !!} for employment in the said manufacturing process as an
        <span style="white-space: nowrap;">{!! $dots($e && $x ? ((F::age($e, $x->examined_on) ?? 18) >= 18 ? 'adult' : 'child') : 'adult/child', 80) !!}.</span>
    </p>
    <p>Physical disability, if any :- {!! $dots($x?->detail('physical_disability'), 300) !!}</p>
    <p>Reason for refusal/revocation of certificate :- {!! $dots($x?->detail('refusal_reason'), 300) !!}</p>
    <div class="signs">
        <div>Signature or left hand thumb<br>impression of the person examined<br>Date :- {{ $x?->examined_on?->format('d-m-Y') }}</div>
        <div><br><strong>Signature of Certifying Surgeon</strong><br>Name (In block letters) : {{ mb_strtoupper($x?->certifying_surgeon ?? '') }}</div>
    </div>
</div>
