<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>
    @include('employees._tabs')

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-5">
            <h2 class="text-xl font-bold text-gray-900">{{ __('Departments') }}</h2>
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('departments.index') }}">
                    <select name="company_id" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('All companies') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->establishment_name }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('departments.create') }}" class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Add department') }}</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-brand-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <th class="px-5 py-3">{{ __('Company') }}</th>
                        <th class="px-5 py-3">{{ __('Department') }}</th>
                        <th class="px-5 py-3">{{ __('Parent department') }}</th>
                        <th class="px-5 py-3">{{ __('Employees') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($departments as $department)
                        <tr>
                            <td class="px-5 py-3 text-gray-500">{{ $departments->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3">{{ $department->company->establishment_name }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $department->name }}</td>
                            <td class="px-5 py-3">{{ $department->parent?->name ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $department->employees_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('departments.edit', $department) }}" class="font-medium text-brand-700 hover:underline">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('{{ __('Delete this department?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="font-medium text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">{{ __('No departments yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($departments->hasPages())
            <div class="border-t border-gray-200 p-4">{{ $departments->links() }}</div>
        @endif
    </div>
</x-app-layout>
