@php
    $period = $periodEnd->format('Y-m');
    $groups = [['Adults', [['(i) Men', 'adult_men'], ['(ii) Women', 'adult_women']]], ['Adolescents', [['(i) Men', 'adolescent_men'], ['(ii) Women', 'adolescent_women']]], ['Children', [['(i) Boys', 'boys'], ['(ii) Girls', 'girls']]]];
    $breakdown = function (array $counts) use ($groups) {
        $html = '';
        foreach ($groups as $gi => [$group, $rows]) {
            foreach ($rows as $ri => [$label, $key]) {
                $html .= '<tr><td style="width:5%">'.($ri === 0 ? '('.chr(97 + $gi).')' : '').'</td><td style="width:20%">'.($ri === 0 ? $group : '').'</td><td>'.$label.'</td><td>:</td><td>'.$counts[$key].'</td></tr>';
            }
        }

        return $html.'<tr><td></td><td></td><td>Total</td><td>:</td><td>'.array_sum($counts).'</td></tr>';
    };
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<h1>Form No. 25</h1>
<p class="sub">(Prescribed under clause (2) of Rule 107)</p>
<p class="caption">Half Yearly Return<br>For the half year ending {{ $half === 'june' ? 'June' : 'December' }}, {{ $year }}.</p>
<p class="small" style="text-align: right;">Registration No.: &nbsp; Licence No.: &nbsp; *NIC Code No.: (As given in the licence)</p>
<table class="info">
    <tr><td style="width:4%">1.</td><td class="label">Name of factory</td><td class="sep">:</td><td>{{ $company->establishment_name }}</td></tr>
    <tr><td>2.</td><td>Name of Occupier</td><td>:</td><td>{{ $contractor }}</td></tr>
    <tr><td>3.</td><td>Name of Manager</td><td>:</td><td></td></tr>
    <tr><td>4.</td><td>District</td><td>:</td><td>{{ $company->district }}</td></tr>
    <tr><td>5.</td><td>Full postal address of the factory (including PIN Code)</td><td>:</td><td>{{ $address }}</td></tr>
    <tr><td>6.</td><td>Industry</td><td>:</td><td></td></tr>
    <tr><td></td><td>(a) Name of Industry</td><td>:</td><td></td></tr>
    <tr><td></td><td>(b) Sector of Industry</td><td>:</td><td></td></tr>
    <tr><td></td><td>(c) Section of the Act under Which the factory is Covered</td><td>:</td><td>2 (m)(i) &nbsp; 2 (m)(ii) &nbsp; Section 85</td></tr>
    <tr><td>7.</td><td>Number of days factory worked during the half year ending {{ $periodEnd->format('jS F Y') }}</td><td>:</td><td>{{ $workingDays }}</td></tr>
    <tr><td>8.</td><td>Number of mandays worked during the half year ending {{ $periodEnd->format('jS F Y') }}</td><td>:</td><td></td></tr>
</table>
<table class="info" style="width: 70%; margin-left: 5%;">{!! $breakdown($mandays) !!}</table>
<table class="info"><tr><td style="width:4%">9.</td><td class="label">Average number of workers employed daily</td><td class="sep">:</td><td></td></tr></table>
<table class="info" style="width: 70%; margin-left: 5%;">{!! $breakdown($average) !!}</table>
<table class="info">
    <tr><td style="width:4%">10.</td><td class="label">Medical Information</td><td class="sep">:</td><td></td></tr>
    <tr><td>a)</td><td>Total numbers of workers employed in hazardous processes</td><td>:</td><td>{{ $hazardous }}</td></tr>
    <tr><td>b)</td><td>Name of the hazardous agents</td><td>:</td><td>@include('compliance.forms.sheets._value', ['key' => 2])</td></tr>
    <tr><td>c)</td><td>Number of the medical officers employed</td><td>:</td><td>@include('compliance.forms.sheets._value', ['key' => 3])</td></tr>
    <tr><td></td><td>i) full_time</td><td>:</td><td>@include('compliance.forms.sheets._value', ['key' => 4])</td></tr>
    <tr><td></td><td>ii) Part_time</td><td>:</td><td>@include('compliance.forms.sheets._value', ['key' => 5])</td></tr>
    <tr><td>d)</td><td>Number of workers Examined by Factory Medical Officer</td><td>:</td><td>@include('compliance.forms.sheets._value', ['key' => 6])</td></tr>
    <tr><td></td><td>i) workers working in hazardous process</td><td>:</td><td>@include('compliance.forms.sheets._value', ['key' => 7])</td></tr>
    <tr><td></td><td>ii) Others</td><td>:</td><td>@include('compliance.forms.sheets._value', ['key' => 8])</td></tr>
</table>
<table class="info" style="margin-top: 20px;">
    <tr><td style="width: 50%;">Date : @include('compliance.forms.sheets._value', ['key' => 9, 'type' => 'date'])</td><td style="text-align: right;">Signature of Manager<br>Name (in block letters)</td></tr>
</table>
<p class="small"><strong>Explanatory Notes :-</strong> Working days are days on which the establishment actually worked and a manufacturing process was carried on.
    Mandays worked are the aggregate attendance of all workers in all shifts on all working days; attendance for half a shift or more counts as full attendance.
    Average number of workers employed daily is mandays worked divided by working days.</p>
@include('compliance.forms.sheets._save', ['period' => $period])
@unless ($printing)
</form>
@endunless
