<h1>Form D - Muster Roll</h1>
<p class="sub">The Contract Labour (Regulation and Abolition) (Gujarat) Rules 1972</p>
<p class="sub">[See Rule 78 (2) (a) ]</p>
@include('compliance.forms._establishment', ['monthLine' => $month->format('F Y')])
<table class="reg">
    <tr>
        <th rowspan="2">Sl no</th><th rowspan="2">Employee no</th><th rowspan="2">Name of employee</th><th rowspan="2">Sex</th><th rowspan="2">Designation</th>
        <th colspan="{{ $month->daysInMonth }}">Daily attendance for the month of {{ $month->format('F Y') }}</th>
        <th rowspan="2">Total number of mandays worked</th><th rowspan="2">Lay off</th><th rowspan="2">Leave day</th>
        <th rowspan="2">Number of festival &amp; national holiday</th><th rowspan="2">Number of weekly holidays (off) paid for</th><th rowspan="2">Total mandays paid for</th><th rowspan="2">Remarks</th>
    </tr>
    <tr>@foreach (range(1, $month->daysInMonth) as $day)<th class="day">{{ $day }}</th>@endforeach</tr>
    @forelse ($employees as $e)
        @php $t = $grid[$e->id]['totals']; @endphp
        <tr>
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $e->employee_code }}</td>
            <td>{{ $e->full_name }}</td>
            <td>{{ $e->gender?->label() }}</td>
            <td>{{ $e->position?->name }}</td>
            @include('compliance.forms.sheets._grid', ['days' => $grid[$e->id]['days']])
            <td class="num">{{ $t['P'] + $t['HD'] / 2 }}</td>
            <td class="num">0</td>
            <td class="num">{{ $t['LEAVE'] + $t['COFF'] }}</td>
            <td class="num">{{ $t['PH'] }}</td>
            <td class="num">{{ $t['WO'] }}</td>
            <td class="num">{{ $t['P'] + $t['HD'] / 2 + $t['LEAVE'] + $t['COFF'] + $t['PH'] + $t['WO'] }}</td>
            <td></td>
        </tr>
    @empty
        <tr><td colspan="{{ 12 + $month->daysInMonth }}" class="empty">{{ __('No employees for this month.') }}</td></tr>
    @endforelse
</table>
