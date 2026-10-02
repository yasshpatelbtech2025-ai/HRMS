<x-app-layout>
    <x-slot name="header">{{ __('Company') }}</x-slot>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-5">
            <h2 class="text-xl font-bold text-gray-900">{{ __('Company list') }}</h2>
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('company.index') }}">
                    <x-text-input name="search" :value="request('search')" :placeholder="__('Search name…')" class="text-sm" />
                </form>
                <a href="{{ route('company.create') }}" class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Add company') }}</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-brand-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <th class="px-5 py-3">{{ __('Establishment name') }}</th>
                        <th class="px-5 py-3">{{ __('Organization size') }}</th>
                        <th class="px-5 py-3">{{ __('Connected company') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($companies as $company)
                        <tr>
                            <td class="px-5 py-3 text-gray-500">{{ $companies->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $company->establishment_name }}</td>
                            <td class="px-5 py-3">{{ $company->organization_size ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $company->parent?->establishment_name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('company.edit', $company) }}" class="font-medium text-brand-700 hover:underline">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('company.destroy', $company) }}" onsubmit="return confirm('{{ __('Delete this company?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="font-medium text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">{{ __('No companies yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($companies->hasPages())
            <div class="border-t border-gray-200 p-4">{{ $companies->links() }}</div>
        @endif
    </div>
</x-app-layout>
