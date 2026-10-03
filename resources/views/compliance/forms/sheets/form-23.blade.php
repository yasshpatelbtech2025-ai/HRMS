@php $m = fn ($n) => number_format((float) $n, 2); @endphp
<p class="sub">Form XXIII</p>
<p class="sub">[See rule 78 (1) (a) (iii)]</p>
<h1>Register of Overtime</h1>
@include('compliance.forms._establishment', ['monthLine' => $month->format('F Y')])
<table class="reg">
    <tr>
        @foreach (['Sl. No.', 'Name of workman', "Father's/ Husband's name", 'Sex', 'Designation/ Nature of employment', 'Date on which overtime worked', 'Total overtime',
            'Normal rate of wages', 'Overtime rate of wages', 'Overtime earnings', 'Date on which overtime wages paid', 'Remarks'] as $h)<th>{{ $h }}</th>@endforeach
    </tr>
    <tr class="colno">@foreach (range(1, 12) as $n)<td>{{ $n }}</td>@endforeach</tr>
    @forelse ($rows as $row)
        @php $e = $row['employee']; @endphp
        <tr>
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $e->first_name }} {{ $e->last_name }}</td>
            <td>{{ \App\Services\Compliance\ComplianceForms::guardian($e) }}</td>
            <td>{{ $e->gender?->label() }}</td>
            <td>{{ $e->position?->name }}</td>
            <td>{{ $row['dates'] }}</td>
            <td class="num">{{ $row['hours'] + 0 }}</td>
            <td class="money">{{ $m($row['normal']) }}</td>
            <td class="money">{{ $m($row['rate']) }}</td>
            <td class="money">{{ $m($row['earnings']) }}</td>
            <td>{{ $row['paid_on'] }}</td>
            <td></td>
        </tr>
    @empty
        <tr><td colspan="12" class="empty">No overtime in {{ $month->format('F Y') }}</td></tr>
    @endforelse
</table>
<p class="small">Rates are per hour.</p>
