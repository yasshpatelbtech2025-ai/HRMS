@use('App\Services\Compliance\ComplianceForms', 'F')
@php
    $e = $employee;
    $x = $examination;
    $dots = fn ($v, $width = 220) => '<span class="dots" style="min-width: '.$width.'px;">'.(e($v ?? '') ?: str_repeat('&nbsp;', 30)).'</span>';
@endphp
<div class="cert">
    <h1>FORM NO. 30</h1>
    <p class="sub">(Prescribed under Schedule VI of Rule 102)</p>
    <p class="sub">Special Certificate of Fitness</p>
    <p class="sub" style="font-size: 15px; font-weight: 400;">(In respect of person employed in operation involving use of lead compounds.)</p>
    <br>
    <p>Serial No. {!! $dots($e?->employee_code, 100) !!},</p>
    <p>Date : {!! $dots($x?->examined_on?->format('d-m-Y'), 120) !!}</p>
    <p class="text">
        I hereby certify that I have personally examined {!! $dots($e?->full_name) !!}
        son of {!! $dots($e ? F::guardian($e) : null) !!}
        residing at {!! $dots($e ? F::residence($e) : null, 320) !!}
        who is desirous of being employed as {!! $dots($e?->position?->name) !!}
        in the {!! $dots(collect([$x?->detail('process') ?? $e?->subDepartment?->name, $company->establishment_name])->filter()->implode(' | '), 280) !!}
        and that his age, as nearly as can be ascertained from my examination, is {!! $dots($e ? F::age($e, $x?->examined_on) : null, 60) !!}
        years, and that he is, in my opinion, {!! $dots($x?->result ? ucfirst($x->result) : 'fit', 80) !!} for employment at work involving the use of lead compounds.
    </p>
    <p>His descriptive marks are {!! $dots($x?->detail('identification_marks'), 300) !!}</p>
    <div class="signs">
        <div></div>
        <div>Certifying Surgeon.{{ $x?->certifying_surgeon ? ' ('.$x->certifying_surgeon.')' : '' }}</div>
    </div>
    <table class="reg" style="margin-top: 24px;">
        <tr>
            <th>I certify that I examined the person mentioned above on</th>
            <th>I extend this certificate until</th>
            <th>Signature of certifying surgeon</th>
            <th>Note of symptoms of lead poisoning (if any)</th>
        </tr>
        <tr style="height: 60px;">
            <td class="nw">{{ $x?->examined_on?->format('d-m-Y') }}</td>
            <td class="nw">{{ $x?->extended_until?->format('d-m-Y') }}</td>
            <td></td>
            <td>{{ $x?->detail('lead_symptoms') }}</td>
        </tr>
    </table>
</div>
