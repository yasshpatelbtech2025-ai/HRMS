@php
    $period = $startYear.'-'.($startYear + 1);
    $n = fn ($key) => (float) $values->value($key);
    $money = fn ($v) => number_format($v, 2);
    $hasFigures = $values->value(2) !== null || $values->value(3) !== null;
    $setOn = $n(2) - $n(3);
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<h1>FORM B</h1>
<p class="sub">[See rule 4 (b)]</p>
<p class="caption">SET ON AND SET-OFF OF ALLOCABLE SURPLUS UNDER SECTION 15</p>
<table class="info">
    <tr><td class="label">Name of the establishment</td><td class="sep">:-</td><td>{{ $company->establishment_name }}</td></tr>
    <tr><td class="label">Address of the establishment</td><td class="sep">:-</td><td>{{ $address }}</td></tr>
</table>
<table class="reg">
    <tr>
        <th>Accounting year</th><th>Amount allocable as bonus (in Rs.)</th><th>Amount payable as bonus (in Rs.)</th>
        <th>Amount of set on or set-off (in Rs.)</th><th>Total set-on or set-off carried forward (in Rs.)</th>
    </tr>
    <tr class="colno">@foreach (range(1, 5) as $col)<td>{{ $col }}</td>@endforeach</tr>
    <tr>
        <td>{{ $startYear }} - {{ $startYear + 1 }}</td>
        @foreach ([2, 3] as $col)
            <td class="money">@if ($printing){{ $values->value($col) !== null ? $money($n($col)) : '' }}@else @include('compliance.forms.sheets._value', ['key' => $col, 'type' => 'number']) @endif</td>
        @endforeach
        <td class="money">{{ $hasFigures ? $money(abs($setOn)).' '.($setOn >= 0 ? '(set on)' : '(set off)') : '' }}</td>
        <td class="money">@if ($printing){{ $values->value(5) !== null ? $money($n(5)) : '' }}@else @include('compliance.forms.sheets._value', ['key' => 5, 'type' => 'number']) @endif</td>
    </tr>
</table>
@unless ($printing)
    <p class="small" style="color: #555;">Column 4 is column 2 minus column 3 when you save: a surplus is set on, a shortfall set off.</p>
@endunless
<p style="text-align: right; margin-top: 40px;">Signature of the Employer or his agent.</p>
@include('compliance.forms.sheets._save', ['period' => $period])
@unless ($printing)
</form>
@endunless
