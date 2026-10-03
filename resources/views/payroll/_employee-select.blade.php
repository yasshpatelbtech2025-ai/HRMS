{{-- Employee <select> for payroll forms. Expects: $id, $employees, $selected (uuid). Optional $name (default "employee"). --}}
<select id="{{ $id }}" name="{{ $name ?? 'employee' }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
    <option value="">{{ __('Select employee') }}</option>
    @foreach ($employees as $option)
        <option value="{{ $option->uuid }}" @selected($selected === $option->uuid)>{{ $option->employee_code }} - {{ $option->full_name }}</option>
    @endforeach
</select>
