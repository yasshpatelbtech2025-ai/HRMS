{{-- The leading cells of a typed-in register row. Expects: $leading ("sr-name" or "name-father-designation"), $employee, $number. --}}
@if ($leading === 'sr-name')
    <td class="num">{{ $employee->employee_code }}</td>
    <td>{{ $employee->full_name }}</td>
@else
    <td class="num">{{ $number }}</td>
    <td>{{ $employee->full_name }}</td>
    <td>{{ \App\Services\Compliance\ComplianceForms::guardian($employee) }}</td>
    <td>{{ $employee->position?->name }}</td>
@endif
