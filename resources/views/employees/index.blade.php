<x-app-layout>
    <x-slot name="header">{{ __('Employees') }}</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <form method="get" action="{{ route('employees.index') }}" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input-label for="search" :value="__('Search')" class="sr-only" />
                    <x-text-input id="search" name="search" type="search" class="block w-full" :value="$filters['search'] ?? ''" :placeholder="__('Name, code or email')" />
                </div>
                <select name="status" aria-label="{{ __('Status') }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (\App\Enums\EmployeeStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ __($status->label()) }}</option>
                    @endforeach
                </select>
                <select name="department" aria-label="{{ __('Department') }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('All departments') }}</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department }}" @selected(($filters['department'] ?? '') === $department)>{{ $department }}</option>
                    @endforeach
                </select>
                <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
            </form>

            <a href="{{ route('employees.create') }}" class="inline-flex items-center justify-center rounded-md bg-brand-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                {{ __('Add employee') }}
            </a>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
            @if ($employees->isEmpty())
                <p class="p-10 text-center text-sm text-gray-500">
                    {{ array_filter($filters) ? __('No employees match these filters.') : __('No employees yet. Add your first one to get started.') }}
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Employee') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Code') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Department') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Designation') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Joined') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($employees as $employee)
                                <tr class="hover:bg-brand-50/50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('employees.show', $employee) }}" class="font-medium text-gray-900 hover:text-brand-700">{{ $employee->full_name }}</a>
                                        <div class="text-gray-500">{{ $employee->email }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $employee->employee_code }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $employee->department ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $employee->designation ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $employee->date_of_joining->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $employee->status->badgeClasses() }}">{{ __($employee->status->label()) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $employees->links() }}
    </div>
</x-app-layout>
