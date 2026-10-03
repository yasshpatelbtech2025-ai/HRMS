@php $m = fn ($n) => number_format((float) $n, 2); @endphp
<p class="sub">Form XXII</p>
<p class="sub">[See rule 78 (1) (a) (ii)]</p>
<h1>Register of Advances</h1>
@include('compliance.forms._establishment')
<table class="reg">
    <tr>
        @foreach (['Sl. No.', 'Name', "Father's/ Husband's name", 'Nature of employment Designation', 'Wage period and wages payable', 'Date & amount of advance given',
            'Purpose(s) for which advance made', 'No. of instalments by which advance to be repaid', 'Date & amount of each instalment re-paid',
            'Date on which last instalment was repaid', 'Remarks'] as $h)<th>{{ $h }}</th>@endforeach
    </tr>
    <tr class="colno">@foreach (range(1, 11) as $n)<td>{{ $n }}</td>@endforeach</tr>
    @forelse ($advances as $a)
        @php
            $e = $a->employee;
            $wage = $e->pay_frequency === 'monthly' ? $e->salaryElements->whereIn('element', config('employee.gross_salary_elements'))->sum('monthly') : $e->daily_rate;
            $repaid = (float) $a->released_amount;
        @endphp
        <tr>
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $e->full_name }}</td>
            <td>{{ \App\Services\Compliance\ComplianceForms::guardian($e) }}</td>
            <td>{{ $e->position?->name }}</td>
            <td>{{ config('employee.pay_frequencies')[$e->pay_frequency] ?? '' }}{{ $wage ? ' — '.$m($wage) : '' }}</td>
            <td class="nw">{{ $a->advance_date->format('d-m-Y') }} — {{ $m($a->amount) }}</td>
            <td>{{ __('Salary advance') }}</td>
            <td class="num">1</td>
            <td class="nw">{{ $repaid > 0 ? $a->salary_month->endOfMonth()->format('d-m-Y').' — '.$m($repaid) : '' }}</td>
            <td class="nw">{{ $repaid >= (float) $a->amount ? $a->salary_month->endOfMonth()->format('d-m-Y') : '' }}</td>
            <td>{{ $a->is_active ? '' : __('Inactive') }}</td>
        </tr>
    @empty
        <tr><td colspan="11" class="empty">No advances records for the month of {{ $month->format('F Y') }}</td></tr>
    @endforelse
</table>
