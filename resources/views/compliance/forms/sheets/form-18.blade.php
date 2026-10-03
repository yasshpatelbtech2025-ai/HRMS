@php $m = fn ($n) => number_format((float) $n, 2); @endphp
<p class="sub">Form XVIII</p>
<p class="sub">[See rule 78 (1) (a) (i)]</p>
<h1>Form of Register of Wages-cum-Muster roll</h1>
@include('compliance.forms._establishment', ['monthLine' => $month->format('F Y'), 'wagePeriod' => 'Monthly'])
<table class="reg">
    <tr>
        <th>Sl. No.</th><th>Serial No. in the register of workman</th><th>Name of Workman</th><th>Designation/ nature of work done</th>
        <th colspan="{{ $month->daysInMonth }}">Daily attendance/ units worked</th>
        @foreach (['Total attendance/ Units of work done', 'Daily rate of wages/piece rate', 'Basic wages', 'Dearness Allowances', 'Overtime', 'Other cash payments', 'Total',
            'Deductions, if any', 'Net amount paid', 'Signature/ Thumb impression of workman', 'Initial of contractor or his re- presentative'] as $h)<th>{{ $h }}</th>@endforeach
    </tr>
    <tr class="colno">
        @foreach (range(1, 4) as $n)<td>{{ $n }}</td>@endforeach
        @foreach (range(1, $month->daysInMonth) as $day)<td>{{ $day }}</td>@endforeach
        @foreach (range(6, 16) as $n)<td>{{ $n }}</td>@endforeach
    </tr>
    @forelse ($employees as $e)
        @php $w = $wages[$e->id]; @endphp
        <tr>
            <td class="num">{{ $loop->iteration }}</td>
            <td class="num">{{ $e->employee_code }}</td>
            <td>{{ $e->full_name }}</td>
            <td>{{ $e->position?->name }}</td>
            @include('compliance.forms.sheets._grid', ['days' => $grid[$e->id]['days']])
            <td class="num">{{ $w ? $w['days'] + 0 : '' }}</td>
            <td class="money">{{ $e->pay_frequency === 'daily' ? $m($e->daily_rate) : '' }}</td>
            <td class="money">{{ $w ? $m($w['basic']) : '' }}</td>
            <td class="money">{{ $w ? $m($w['da']) : '' }}</td>
            <td class="money">{{ $w ? $m($w['overtime']) : '' }}</td>
            <td class="money">{{ $w ? $m($w['other']) : '' }}</td>
            <td class="money">{{ $w ? $m($w['gross']) : '' }}</td>
            <td class="money">{{ $w ? $m($w['deductions']) : '' }}</td>
            <td class="money">{{ $w ? $m($w['net']) : '' }}</td>
            <td></td><td></td>
        </tr>
    @empty
        <tr><td colspan="{{ 15 + $month->daysInMonth }}" class="empty">{{ __('No employees for this month.') }}</td></tr>
    @endforelse
</table>
