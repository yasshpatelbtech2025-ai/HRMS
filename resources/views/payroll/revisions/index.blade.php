<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php
        $money = fn ($n) => number_format((float) $n, 2);
        $badge = ['pending' => 'bg-amber-100 text-amber-800', 'applied' => 'bg-green-100 text-green-800', 'cancelled' => 'bg-gray-100 text-gray-600'];
    @endphp

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Salary revision list') }}</h2>
            <form method="GET" action="{{ route('payroll.revisions') }}" class="flex flex-wrap items-center gap-2"
                  x-data="{ employee: @js($employee?->uuid ?? '') }">
                <select name="employee" x-model="employee" onchange="this.form.submit()" class="min-w-72 rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" aria-label="{{ __('Employee') }}">
                    <option value="">{{ __('Select employee') }}</option>
                    @foreach ($employees as $option)
                        <option value="{{ $option->uuid }}">{{ $option->employee_code }} - {{ $option->full_name }}</option>
                    @endforeach
                </select>
                <a :href="employee ? @js(route('payroll.revisions.create', '__UUID__')).replace('__UUID__', employee) : null"
                   x-on:click="if (! employee) { $event.preventDefault(); alert(@js(__('Please select employee'))); }"
                   class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    {{ __('Add salary revision') }}
                </a>
                <a href="{{ route('payroll.revisions.export', ['format' => 'csv'] + request()->only('employee')) }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('CSV') }}</a>
                <a href="{{ route('payroll.revisions.export', ['format' => 'xlsx'] + request()->only('employee')) }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Excel') }}</a>
            </form>
        </div>
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        @foreach (['Employee id', 'Name of employee', 'Revised date', 'Effective from', 'Revised salary (yearly)', 'Revised percentage %', 'Status', 'Action'] as $heading)
                            <th class="px-4 py-3">{{ __($heading) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($revisions as $revision)
                        @php $pct = $revision->percentage(); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono">{{ $revision->employee->employee_code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $revision->employee->full_name }}</td>
                            <td class="px-4 py-3">{{ $revision->revised_on->toDateString() }}</td>
                            <td class="px-4 py-3">{{ $revision->effective_from->format('F Y') }}</td>
                            <td class="px-4 py-3">{{ $money($revision->revised_yearly) }} <span class="text-xs text-gray-500">({{ __('was :amount', ['amount' => $money($revision->previous_yearly)]) }})</span></td>
                            <td class="px-4 py-3 font-semibold {{ $pct >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $pct >= 0 ? '+' : '' }}{{ $pct }}%</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[$revision->status] }}">{{ __(ucfirst($revision->status)) }}</span></td>
                            <td class="px-4 py-3">
                                @if ($revision->status === 'pending')
                                    <div class="flex gap-1">
                                        <form method="POST" action="{{ route('payroll.revisions.apply', $revision) }}" data-confirm="{{ __('Apply this revision now?') }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="rounded-md bg-brand-600 px-2 py-1 text-xs font-semibold text-white hover:bg-brand-700">{{ __('Apply now') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('payroll.revisions.cancel', $revision) }}" data-confirm="{{ __('Cancel this revision?') }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">{{ __('Cancel') }}</button>
                                        </form>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="8" icon="inbox">{{ __('No salary revisions yet.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($revisions->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">{{ $revisions->links() }}</div>
        @endif
    </div>
    </div>
</x-app-layout>
