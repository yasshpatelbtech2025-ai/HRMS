@use('App\Services\Compliance\ComplianceForms', 'F')
<p class="sub">Form XIII</p>
<p class="sub">{See rule 75}</p>
<h1>Register of Workmen Employed by Contractor</h1>
@include('compliance.forms._establishment')
<table class="reg">
    <tr>
        @foreach (['Sl. No.', 'Name and surname of workman', 'Age and Sex', "Father's/ Husband's name", 'Nature of employment/ Designation', 'Permanent home address of workman',
            'Local address', 'Date of commencement of employment', 'Signature or thumb impression of workman', 'Date of termination of employment', 'Reasons for termination', 'Remarks'] as $h)<th>{{ $h }}</th>@endforeach
    </tr>
    <tr class="colno">@foreach (range(1, 12) as $n)<td>{{ $n }}</td>@endforeach</tr>
    @forelse ($employees as $e)
        <tr>
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $e->first_name }} {{ $e->last_name }}</td>
            <td>{{ $e->gender?->label() }}{{ $e->date_of_birth ? '. '.$e->date_of_birth->age : '' }}</td>
            <td>{{ F::guardian($e) }}</td>
            <td>{{ $e->position?->name }}</td>
            <td>{{ F::address($e->address('permanent')) }}</td>
            <td>{{ F::address($e->address('present')) }}</td>
            <td>{{ $e->date_of_joining?->format('d-m-Y') }}</td>
            <td></td>
            <td>{{ $e->separation_date?->format('d-m-Y') }}</td>
            <td></td>
            <td></td>
        </tr>
    @empty
        <tr><td colspan="12" class="empty">{{ __('No employees yet.') }}</td></tr>
    @endforelse
</table>
