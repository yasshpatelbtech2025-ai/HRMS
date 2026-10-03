@php
    $period = $startYear.'-'.($startYear + 1);
    $n = fn ($key) => (float) $values->value($key);
    $deducted = $n(2) + $n(3) + $n(4) + $n(5);
    $available = $n(1) - $deducted;
    $money = fn ($v) => number_format($v, 2);
    $hasFigures = $values->value(1) !== null;
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<h1>FORM A</h1>
<p class="sub">[See rule 4 (a)]</p>
<p class="caption">Computation of allocable surplus under sec. 2(4)</p>
<table class="info">
    <tr><td class="label">Name of the establishment</td><td class="sep">:-</td><td>{{ $company->establishment_name }}</td></tr>
    <tr><td class="label">Address of the establishment</td><td class="sep">:-</td><td>{{ $address }}</td></tr>
    <tr><td class="label">Accounting year ending on the</td><td class="sep">:-</td><td>{{ $yearEnd->format('jS F Y') }} ({{ $period }})</td></tr>
</table>
<table class="reg">
    <tr>
        <th rowspan="2">Gross profits for the accounting year (Rs.)</th>
        <th colspan="5">Sums deducted from gross profits</th>
        <th rowspan="2">Available surplus for the accounting year (col. 1 minus col. 6)</th>
        <th rowspan="2">Amount of allocable surplus: 60 per cent / 67 per cent of col. 7 (Rs.)</th>
    </tr>
    <tr>
        <th>Depreciation under [Sec. 6 (a)]</th><th>Development rebate or development allowance [Sec. 6(b)]</th><th>Direct taxes [Sec. 6 (c)]</th>
        <th>Further sums as are specified under the third schedule of the Act</th><th>Total of sums deducted (cols. 2, 3, 4 and 5)</th>
    </tr>
    <tr class="colno">@foreach (range(1, 8) as $col)<td>{{ $col }}</td>@endforeach</tr>
    <tr>
        @foreach (range(1, 5) as $col)
            <td class="money">@if ($printing){{ $values->value($col) !== null ? $money($n($col)) : '' }}@else @include('compliance.forms.sheets._value', ['key' => $col, 'type' => 'number', 'width' => 110]) @endif</td>
        @endforeach
        <td class="money">{{ $hasFigures ? $money($deducted) : '' }}</td>
        <td class="money">{{ $hasFigures ? $money($available) : '' }}</td>
        <td class="money">@if ($printing){{ $values->value(8) !== null ? $money($n(8)) : '' }}@else @include('compliance.forms.sheets._value', ['key' => 8, 'type' => 'number', 'width' => 110]) @endif</td>
    </tr>
</table>
@unless ($printing)
    <p class="small" style="color: #555;">Columns 6 and 7 are worked out from columns 1 to 5 when you save. Allocable surplus is 67% of col. 7 for a foreign company and 60% for others{{ $hasFigures ? ' — here '.$money($available * 0.6).' (60%) or '.$money($available * 0.67).' (67%)' : '' }}.</p>
@endunless
<p style="text-align: right; margin-top: 40px;">Signature of the Employer or his agent.</p>
@include('compliance.forms.sheets._save', ['period' => $period])
@unless ($printing)
</form>
@endunless
