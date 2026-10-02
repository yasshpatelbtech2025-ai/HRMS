<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>
    @include('employees._tabs')

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between border-b border-gray-200 p-5">
            <h2 class="text-xl font-bold text-gray-900">{{ __('Positions') }}</h2>
            <a href="{{ route('positions.create') }}" class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Add position') }}</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-brand-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <th class="px-5 py-3">{{ __('Position') }}</th>
                        <th class="px-5 py-3">{{ __('Employees') }}</th>
                        <th class="px-5 py-3 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($positions as $position)
                        <tr>
                            <td class="px-5 py-3 text-gray-500">{{ $positions->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $position->name }}</td>
                            <td class="px-5 py-3">{{ $position->employees_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('positions.edit', $position) }}" class="font-medium text-brand-700 hover:underline">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('positions.destroy', $position) }}" onsubmit="return confirm('{{ __('Delete this position?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="font-medium text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">{{ __('No positions yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($positions->hasPages())
            <div class="border-t border-gray-200 p-4">{{ $positions->links() }}</div>
        @endif
    </div>
</x-app-layout>
