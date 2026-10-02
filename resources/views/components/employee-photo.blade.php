{{-- The employee's photo, or their initials when none has been uploaded. --}}
@props(['employee'])

@if ($employee->photo_path)
    <img src="{{ route('employees.file', [$employee, 'photo']) }}" alt="{{ $employee->full_name }}" loading="lazy"
         {{ $attributes->merge(['class' => 'rounded-full object-cover ring-1 ring-gray-200']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-500 ring-1 ring-gray-200']) }} aria-hidden="true">
        {{ mb_strtoupper(mb_substr($employee->first_name, 0, 1).mb_substr($employee->last_name, 0, 1)) }}
    </span>
@endif
