<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>
    @include('employees._tabs')

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-5">
            <h2 class="text-xl font-bold text-gray-900">{{ $title }}</h2>
            @unless ($separatedView)
                <a href="{{ route('employees.create') }}" class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Add employee') }}</a>
            @endunless
        </div>

        <form method="GET" class="flex flex-wrap gap-3 border-b border-gray-200 p-5 text-sm" x-data="{ companyId: @js((string) request('company_id')), all: @js($departments) }">
            <select name="company_id" x-model="companyId" class="rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">{{ __('All companies') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->establishment_name }}</option>
                @endforeach
            </select>
            <select name="department_id" class="rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">{{ __('All departments') }}</option>
                <template x-for="d in all.filter(d => !companyId || String(d.company_id) === String(companyId))" :key="d.id">
                    <option :value="d.id" x-text="d.name" :selected="String(d.id) === @js((string) request('department_id'))"></option>
                </template>
            </select>
            <x-text-input name="search" :value="request('search')" :placeholder="__('Name, ID or mobile')" class="text-sm" />
            <button class="rounded-md bg-gray-800 px-4 py-2 font-semibold text-white hover:bg-gray-700">{{ __('Filter') }}</button>
            @if (request()->hasAny(['company_id', 'department_id', 'search']))
                <a href="{{ url()->current() }}" class="self-center text-gray-600 hover:text-gray-900">{{ __('Clear') }}</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-brand-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Sl') }}</th>
                        <th class="px-4 py-3">{{ __('Company') }}</th>
                        <th class="px-4 py-3">{{ __('Employee ID') }}</th>
                        <th class="px-4 py-3">{{ __('Name') }}</th>
                        <th class="px-4 py-3">{{ __('Mobile') }}</th>
                        <th class="px-4 py-3">{{ __('Department') }}</th>
                        <th class="px-4 py-3">{{ __('Designation') }}</th>
                        <th class="px-4 py-3">{{ __('Joining date') }}</th>
                        @if ($separatedView)
                            <th class="px-4 py-3">{{ __('Separation date') }}</th>
                        @endif
                        <th class="px-4 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="px-4 py-3 text-gray-500">{{ $employees->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3">{{ $employee->company->establishment_name }}</td>
                            <td class="px-4 py-3">{{ $employee->employee_code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $employee->full_name }}</td>
                            <td class="px-4 py-3">{{ $employee->mobile ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $employee->department?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $employee->position?->name ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $employee->joining_date->format('d M Y') }}</td>
                            @if ($separatedView)
                                <td class="px-4 py-3 whitespace-nowrap">{{ $employee->separation_date?->format('d M Y') ?? '—' }}</td>
                            @endif
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('employees.edit', $employee) }}" class="font-medium text-brand-700 hover:underline">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('{{ __('Delete this employee?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="font-medium text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-5 py-10 text-center text-gray-500">{{ __('No employees found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($employees->hasPages())
            <div class="border-t border-gray-200 p-4">{{ $employees->links() }}</div>
        @endif
    </div>
</x-app-layout>
