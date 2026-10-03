@use('App\Services\Compliance\ComplianceForms', 'F')
<h1>Register of Adult Workers</h1>
<p class="sub">{{ $company->establishment_name }}{{ $address ? ', '.$address : '' }}</p>
<br>
<table class="reg">
    <tr>
        <th rowspan="2">S no</th><th rowspan="2">Name</th><th rowspan="2">Date of Birth</th><th rowspan="2">Sex</th>
        <th rowspan="2">Residential Address</th><th rowspan="2">Father's / husband's name</th><th rowspan="2">Date of appointment</th>
        <th colspan="2">Group of which worker belongs</th><th rowspan="2">Number of relay if working in shifts</th>
        <th colspan="2">Adolescent if certified as adults</th><th rowspan="2">Remarks</th>
    </tr>
    <tr>
        <th>Alphabet assigned</th><th>Nature of Work</th><th>Number &amp; date of certificate of fitness</th><th>Number under section 68</th>
    </tr>
    @forelse ($employees as $e)
        @php $adult = (F::age($e) ?? 18) >= 18; @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $e->full_name }}</td>
            <td class="nw">{{ $e->date_of_birth?->format('d-m-Y') }}</td>
            <td>{{ $e->gender?->label() }}</td>
            <td>{{ F::residence($e) }}</td>
            <td>{{ F::guardian($e) }}</td>
            <td class="nw">{{ $e->date_of_joining?->format('d-m-Y') }}</td>
            <td>{{ mb_strtoupper(mb_substr($e->subDepartment?->name ?? '', 0, 1)) }}</td>
            <td>{{ $e->position?->name }}</td>
            <td>{{ $e->shift?->name }}</td>
            <td>{{ $adult ? 'N/A' : '' }}</td>
            <td>{{ $adult ? 'N/A' : '' }}</td>
            <td></td>
        </tr>
    @empty
        <tr><td colspan="13" class="empty">No current employees</td></tr>
    @endforelse
</table>
