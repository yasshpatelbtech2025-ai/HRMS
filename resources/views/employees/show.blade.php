@php
    $details = [
        __('Personal details') => [
            __('Email') => $employee->email,
            __('Phone') => $employee->phone,
            __('Gender') => $employee->gender ? __($employee->gender->label()) : null,
            __('Date of birth') => $employee->date_of_birth?->format('d M Y'),
            __('Address') => $employee->address,
        ],
        __('Employment') => [
            __('Employee code') => $employee->employee_code,
            __('Date of joining') => $employee->date_of_joining->format('d M Y'),
            __('Department') => $employee->department,
            __('Designation') => $employee->designation,
            __('Employment type') => __($employee->employment_type->label()),
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">{{ $employee->full_name }}</x-slot>

    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-lg font-semibold text-brand-700">
                    {{ strtoupper(mb_substr($employee->first_name, 0, 1).mb_substr($employee->last_name, 0, 1)) }}
                </span>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">{{ $employee->full_name }}</h2>
                    <p class="text-sm text-gray-500">{{ collect([$employee->designation, $employee->department])->filter()->implode(' · ') ?: $employee->employee_code }}</p>
                    <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $employee->status->badgeClasses() }}">{{ __($employee->status->label()) }}</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('employees.edit', $employee) }}" class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">{{ __('Edit') }}</a>
                <form method="post" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm(@js(__('Remove :name? Their record will be archived.', ['name' => $employee->full_name])))">
                    @csrf
                    @method('delete')
                    <x-danger-button>{{ __('Remove') }}</x-danger-button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @foreach ($details as $section => $rows)
                <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">{{ $section }}</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        @foreach ($rows as $label => $value)
                            <div class="grid grid-cols-3 gap-2">
                                <dt class="text-gray-500">{{ $label }}</dt>
                                <dd class="col-span-2 text-gray-900">{{ filled($value) ? $value : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>

        <a href="{{ route('employees.index') }}" class="inline-block text-sm font-medium text-brand-700 hover:text-brand-600">&larr; {{ __('Back to employees') }}</a>
    </div>
</x-app-layout>
