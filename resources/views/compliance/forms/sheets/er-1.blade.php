@php
    $period = $quarterEnd->format('Y-m');
    $value = fn (string $key, string $type = 'text') => view('compliance.forms.sheets._value', ['values' => $values, 'key' => $key, 'type' => $type, 'printing' => $printing])->render();
    $c = $counts;
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<div class="cert">
    <h1>FORM ER-1</h1>
    <p style="text-align: center;">Quarterly return to be submitted to the local Employment Exchange for the quarter ended <strong>{{ $quarterEnd->format('M-Y') }}</strong></p>
    <p>The following information is required under the Employment Exchanges (Compulsory Notification of Vacancies) Rules, 1960 to assist in evaluating trends in employment and for action to correct imbalance between labour supply and demand.</p>
    <table class="fields">
        <tr><td colspan="2" class="label">Name &amp; Address of the Employer</td><td class="sep">:</td><td><strong>{{ $company->establishment_name }}</strong>{{ $address ? ', '.$address : '' }}</td></tr>
        <tr><td colspan="2" class="label">Whether Head Office Phone No.</td><td class="sep">:</td><td>{!! $value('head_office_phone') !!}</td></tr>
        <tr><td colspan="2" class="label">Branch Office and Telephone No.</td><td class="sep">:</td><td>{!! $value('branch_office') !!}</td></tr>
        <tr><td colspan="2" class="label">Nature of business/principal activity</td><td class="sep">:</td><td>{!! $value('nature_of_business') !!}</td></tr>
    </table>

    <table class="fields">
        <tr><td class="no"><strong>1.</strong></td><td class="no">(a)</td><td><strong>EMPLOYMENT:</strong></td></tr>
        <tr><td></td><td colspan="2">Total number of persons including working proprietors/partners commission agents/contingent paid and contractual workers on the pay rolls of the establishment excluding part-time workers and apprentices. (The figures should include every person whose wage or salary is paid by the establishment)</td></tr>
    </table>
    <table class="reg">
        <tr>
            <th></th>
            <th>On the last working day of the previous quarter, i.e. {{ $previousEnd->format('M-Y') }}</th>
            <th>On the last working day of the quarter under report, i.e. {{ $quarterEnd->format('M-Y') }}@if ($running && ! $printing)<br><span class="small" style="font-weight: 400;">(quarter not over: counted as on today)</span>@endif</th>
        </tr>
        @foreach (['men' => 'MEN', 'women' => 'WOMEN', 'total' => 'TOTAL'] as $key => $label)
            <tr><td style="text-align: left; font-weight: {{ $key === 'total' ? 700 : 400 }};">{{ $label }}</td><td>{{ $c['previous'][$key] }}</td><td>{{ $c['current'][$key] }}</td></tr>
        @endforeach
    </table>
    <table class="fields">
        <tr><td class="no"></td><td class="no">(b)</td><td>Please indicate the main reasons for any increase or decrease in employment if the increase or decrease is more than 5% during the quarter : {!! $value('change_reasons') !!}</td></tr>
        <tr><td></td><td colspan="2" style="text-decoration: underline;">NOTES:-</td></tr>
        <tr><td></td><td colspan="2">Establishments are reminded of their obligation under the Employment Exchanges (Compulsory Notification of Vacancies) Act for notifying the Employment Exchanges details of vacancies specified under the act, before they are filled.</td></tr>
        <tr><td class="no"><strong>2.</strong></td><td colspan="2"><strong>VACANCIES:</strong></td></tr>
        <tr><td></td><td colspan="2">Vacancies carrying total emoluments of Rs.60/- or over per month and of over three months duration.</td></tr>
        <tr><td></td><td colspan="2">2(a) Number of vacancies occurred and notified during the quarter and the number filled during the quarter.</td></tr>
    </table>
    <table class="reg">
        <tr><th colspan="5">Number of vacancies which come within the purview of the Act.</th></tr>
        <tr><th rowspan="2">Occurred</th><th colspan="2">Notified</th><th rowspan="2">Filled</th><th rowspan="2">Source (Describe the sources from which filled)</th></tr>
        <tr><th>Local Emp. Exchange</th><th>C.E.E.</th></tr>
        <tr>
            <td>{!! $value('vacancies_occurred', 'number') !!}</td><td>{!! $value('notified_local', 'number') !!}</td><td>{!! $value('notified_cee', 'number') !!}</td>
            <td>{!! $value('vacancies_filled', 'number') !!}</td><td>{!! $value('vacancy_source') !!}</td>
        </tr>
    </table>
    <table class="fields">
        <tr><td class="no"></td><td>2(b) Reasons for not notifying all vacancies occurred during the quarter under report vide 2 (a) above : {!! $value('not_notified_reasons') !!}</td></tr>
    </table>
    <p style="text-align: center; text-decoration: underline; margin-top: 12px;">MANPOWER SHORTAGE</p>
    <p>Vacancies/Posts unfilled because of shortage of suitable applicants.</p>
    <table class="reg">
        <tr><th rowspan="2">Name of the occupation or designation of the post.</th><th colspan="3">Number of unfilled vacancies/posts</th></tr>
        <tr><th>Essential qualifications prescribed</th><th>Essential experience</th><th>Experience not necessary</th></tr>
        @foreach (range(1, 4) as $row)
            <tr>
                <td style="text-align: left;">{{ $row }}. {!! $value("shortage_{$row}_post") !!}</td>
                <td>{!! $value("shortage_{$row}_qualifications") !!}</td>
                <td>{!! $value("shortage_{$row}_experience") !!}</td>
                <td>{!! $value("shortage_{$row}_no_experience") !!}</td>
            </tr>
        @endforeach
    </table>
    <p>Please list any other occupations for which these establishments had recently any difficulty in obtaining suitable applicants : {!! $value('other_occupations') !!}</p>
    <div class="signs">
        <div></div>
        <div>Signature of Employer.</div>
    </div>
    <p class="small" style="margin-top: 24px;">This return shall relate to quarters ending 31st March, 30th June, 30th September and 31st December and be rendered to the local Employment Exchange within 30 days after the end of the quarter concerned.</p>
</div>
@unless ($printing)
    @include('compliance.forms.sheets._save', ['period' => $period])
</form>
@endunless
