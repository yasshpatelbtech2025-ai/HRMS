<p class="sub">Form XVI</p>
<p class="sub">[See rule 78 (1) (a) (i)]</p>
<h1>Muster Roll</h1>
@include('compliance.forms._establishment', ['monthLine' => $month->format('F Y')])
<table class="reg">
    <tr>
        <th rowspan="2">Sl no</th><th rowspan="2">Employee no</th><th rowspan="2">Name of employee</th><th rowspan="2">Sex</th><th rowspan="2">Designation</th>
        <th colspan="{{ $month->daysInMonth }}">Daily attendance for the month of {{ $month->format('F Y') }}</th><th rowspan="2">Remarks</th>
    </tr>
    <tr>@foreach (range(1, $month->daysInMonth) as $day)<th class="day">{{ $day }}</th>@endforeach</tr>
    @forelse ($employees as $e)
        <tr>
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $e->employee_code }}</td>
            <td>{{ $e->full_name }}</td>
            <td>{{ $e->gender?->label() }}</td>
            <td>{{ $e->position?->name }}</td>
            @include('compliance.forms.sheets._grid', ['days' => $grid[$e->id]['days']])
            <td></td>
        </tr>
    @empty
        <tr><td colspan="{{ 6 + $month->daysInMonth }}" class="empty">{{ __('No employees for this month.') }}</td></tr>
    @endforelse
</table>
