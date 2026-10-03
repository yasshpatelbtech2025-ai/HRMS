@php
    $period = $month->format('Y-m');
    $money = fn ($v) => number_format((float) $v, 2);
@endphp
@unless ($printing)
<form method="POST" action="{{ route('compliance.forms.values', [$company, $form]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="period" value="{{ $period }}">
@endunless
<h1>PF Contribution Statement</h1>
<p class="sub">{{ $company->establishment_name }} &mdash; {{ $month->format('F Y') }}</p>
<table class="reg">
    <tr>
        <th>Sr No</th><th>Employee Id</th><th>Employee Name</th><th>UAN</th><th>Gross Salary</th><th>EPF WAGE</th><th>EPS WAGE</th><th>EDLI WAGES</th>
        <th>EE</th><th>EPS</th><th>ER</th><th>Absent</th>
    </tr>
    @forelse ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $r['employee']?->employee_code }}</td>
            <td style="text-align: left;">{{ $r['employee']?->full_name }}</td>
            <td>{{ $r['employee']?->uan_code }}</td>
            <td class="money">{{ $money($r['gross']) }}</td>
            <td class="money">{{ $money($r['epf']) }}</td>
            <td class="money">{{ $money($r['eps']) }}</td>
            <td class="money">{{ $money($r['edli']) }}</td>
            <td class="money">{{ $money($r['ee']) }}</td>
            <td class="money">{{ $money($r['eps_share']) }}</td>
            <td class="money">{{ $money($r['er']) }}</td>
            <td>{{ $r['absent'] + 0 }}</td>
        </tr>
    @empty
        <tr><td colspan="12" class="empty">No salary has been generated for {{ $month->format('F Y') }} yet (Payroll › Salary Generate)</td></tr>
    @endforelse
    @if ($rows->isNotEmpty())
        <tr style="font-weight: 700;">
            <td colspan="4" style="text-align: left;">Total</td>
            @foreach (['gross', 'epf', 'eps', 'edli', 'ee', 'eps_share', 'er'] as $key)<td class="money">{{ $money($sums[$key]) }}</td>@endforeach
            <td></td>
        </tr>
    @endif
</table>
<table class="reg" style="width: auto; min-width: 420px;">
    @foreach ($totals as $label => $amount)
        <tr @if (in_array($label, ['TOTAL PF', 'AC 1'], true)) style="font-weight: 700;" @endif>
            <td style="text-align: left;">{{ $label }}</td><td class="money" style="text-align: right;">{{ $money($amount) }}</td>
        </tr>
    @endforeach
</table>
@unless ($printing)
    <p class="small" style="color: #555;">
        From the {{ $month->format('F Y') }} payslips. EE is the employee's 12%; EPS the employer's 8.33% pension share and ER the employer's 3.67% EPF.
        AC 2 is 0.5% of EPF wages (at least ₹500) and AC 21 is 0.5% of EDLI wages. TOTAL PF = EE + ER + EPS + AC 2 + AC 21.
    </p>
    @include('compliance.forms.sheets._save', ['period' => $period, 'label' => __('Upload statement (challan / ECR)')])
</form>
@endunless
