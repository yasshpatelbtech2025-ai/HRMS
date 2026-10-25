{{-- Employee <select> for payroll forms. Expects: $id, $employees, $selected (uuid). Optional $name (default "employee"). --}}
<select id="{{ $id }}" name="{{ $name ?? 'employee' }}" required class="control">
    <option value="">{{ __('Select employee') }}</option>
    @foreach ($employees as $option)
        <option value="{{ $option->uuid }}" @selected($selected === $option->uuid)>{{ $option->employee_code }} - {{ $option->full_name }}</option>
    @endforeach
</select>
