@use('App\Services\Compliance\ComplianceForms', 'F')
@php
    $e = $employee;
    $x = $examination;
    $dots = fn ($v, $width = 220) => '<span class="dots" style="min-width: '.$width.'px;">'.(e($v ?? '') ?: str_repeat('&nbsp;', 30)).'</span>';
@endphp
<div class="cert">
    <h1>FORM NO. 27-A</h1>
    <p class="sub">(Prescribed under Rule 102)</p>
    <p class="sub">CERTIFICATE OF FITNESS</p>
    <br>
    <p>Serial Number {!! $dots($e?->employee_code, 100) !!},</p>
    <p class="text">
        I certify that I have personally examined (name) {!! $dots($e?->full_name) !!}
        son of (Father's name) {!! $dots($e ? F::guardian($e) : null) !!}
        residing at (address) {!! $dots($e ? F::residence($e) : null, 320) !!}
        who is desirous of being employed as (designation) {!! $dots($e?->position?->name) !!}
        in (process, department and factory) {!! $dots(collect([$x?->detail('process') ?? $e?->subDepartment?->name, $company->establishment_name])->filter()->implode(' | '), 280) !!}
        and that his age, as nearly as can be ascertained from my examination, is {!! $dots($e ? F::age($e, $x?->examined_on) : null, 60) !!}
        years, and that he is, in my opinion, {!! $dots($x?->result ? ucfirst($x->result) : 'fit/unfit', 80) !!} for employment in the above mentioned factory as mentioned above.
    </p>
    <div class="signs">
        <div>Signature or left hand<br>thumb impression of person<br>examined.</div>
        <div>Signature of Certifying<br>Surgeon{{ $x?->certifying_surgeon ? ' ('.$x->certifying_surgeon.')' : '' }}<br>Date : {{ $x?->examined_on?->format('d-m-Y') }}</div>
    </div>
    <table class="reg" style="margin-top: 24px;">
        <tr>
            <th>I certify that I examined the person mentioned above on.</th>
            <th>I extend this certificate until (If certificate is not extended, the period for which the worker is considered unfit for work is to be mentioned).</th>
            <th>Signs and symptoms observed during examination.</th>
            <th style="width: 120px;">Signature of the certifying surgeon.</th>
        </tr>
        <tr style="height: 60px;">
            <td class="nw">{{ $x?->examined_on?->format('d-m-Y') }}</td>
            <td>{{ $x?->extended_until?->format('d-m-Y') ?? $x?->detail('withdrawal_period') }}</td>
            <td>{{ $x?->signs_symptoms }}</td>
            <td></td>
        </tr>
    </table>
</div>
