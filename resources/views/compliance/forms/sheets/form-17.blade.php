@php $m = fn ($n) => number_format((float) $n, 2); @endphp
<p class="sub">Form XVII</p>
<p class="sub">[See rule 78 (1) (a) (i)]</p>
<h1>Register of wages</h1>
@include('compliance.forms._establishment', ['monthLine' => $month->format('F Y'), 'wagePeriod' => 'Monthly'])
<table class="reg">
    <tr>
        @foreach (['Sl. No.', 'Name of Workman', 'Serial No. in the register of workman', 'Designation/ nature of work done', 'No. of days worked', 'Units of work done',
            'Daily rate of wages/piece rate', 'Basic wages', 'Dearness Allowances', 'Overtime', 'Other cash payments', 'Total', 'Deductions, if any', 'Net amount paid',
            'Signature/ Thumb impression of workman', 'Initial of contractor or his re- presentative'] as $h)<th>{{ $h }}</th>@endforeach
    </tr>
    <tr class="colno">@foreach (range(1, 16) as $n)<td>{{ $n }}</td>@endforeach</tr>
    @forelse ($employees as $e)
        @php $w = $wages[$e->id]; @endphp
        <tr>
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $e->full_name }}</td>
            <td class="num">{{ $e->employee_code }}</td>
            <td>{{ $e->position?->name }}</td>
            <td class="num">{{ $w ? $w['days'] + 0 : 0 }}</td>
            <td class="num">0</td>
            <td class="money">{{ $e->pay_frequency === 'daily' ? $m($e->daily_rate) : ($e->pay_frequency === 'hourly' ? $m($e->hourly_rate) : '-') }}</td>
            <td class="money">{{ $w ? $m($w['basic']) : 0 }}</td>
            <td class="money">{{ $w ? $m($w['da']) : 0 }}</td>
            <td class="money">{{ $w ? $m($w['overtime']) : 0 }}</td>
            <td class="money">{{ $w && $w['other'] ? $m($w['other']) : 'N/A' }}</td>
            <td class="money">{{ $w ? $m($w['gross']) : 0 }}</td>
            <td class="money">{{ $w ? $m($w['deductions']) : 0 }}</td>
            <td class="money">{{ $w ? $m($w['net']) : 0 }}</td>
            <td></td><td></td>
        </tr>
    @empty
        <tr><td colspan="16" class="empty">{{ __('No employees for this month.') }}</td></tr>
    @endforelse
</table>
