<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php
        $money = fn ($n) => number_format((float) $n, 2);
        $badge = ['pending' => 'bg-warn-soft text-warn', 'applied' => 'bg-success-soft text-success', 'cancelled' => 'bg-gray-100 text-gray-600'];
    @endphp

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Salary revision list') }}</h1>
            <form method="GET" action="{{ route('payroll.revisions') }}" class="flex flex-wrap items-center gap-2"
                  x-data="{ employee: @js($employee?->uuid ?? '') }">
                <select name="employee" x-model="employee" onchange="this.form.submit()" class="min-w-72 control-inline" aria-label="{{ __('Employee') }}">
                    <option value="">{{ __('Select employee') }}</option>
                    @foreach ($employees as $option)
                        <option value="{{ $option->uuid }}">{{ $option->employee_code }} - {{ $option->full_name }}</option>
                    @endforeach
                </select>
                <a :href="employee ? @js(route('payroll.revisions.create', '__UUID__')).replace('__UUID__', employee) : null"
                   x-on:click="if (! employee) { $event.preventDefault(); alert(@js(__('Please select employee'))); }"
                   class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">
                    {{ __('Add salary revision') }}
                </a>
                <a href="{{ route('payroll.revisions.export', ['format' => 'csv'] + request()->only('employee')) }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('CSV') }}</a>
                <a href="{{ route('payroll.revisions.export', ['format' => 'xlsx'] + request()->only('employee')) }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Excel') }}</a>
            </form>
        </div>
        <x-stat-strip :items="[
            ['label' => __('Revisions'), 'value' => array_sum($counts)],
            ['label' => __('Scheduled'), 'value' => $counts['pending'] ?? 0, 'tone' => ($counts['pending'] ?? 0) ? 'warn' : null, 'hint' => __('Apply on their effective date')],
            ['label' => __('Applied'), 'value' => $counts['applied'] ?? 0, 'tone' => 'success'],
        ]" />

        <div class="card">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        @foreach (['Employee id', 'Name of employee', 'Revised date', 'Effective from', 'Revised salary (yearly)', 'Revised percentage %', 'Status', 'Action'] as $heading)
                            <th class="px-4 py-3">{{ __($heading) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($revisions as $revision)
                        @php $pct = $revision->percentage(); @endphp
                        <tr>
                            <td class="px-4 py-3 font-mono">{{ $revision->employee->employee_code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $revision->employee->full_name }}</td>
                            <td class="px-4 py-3">{{ $revision->revised_on->toDateString() }}</td>
                            <td class="px-4 py-3">{{ $revision->effective_from->format('F Y') }}</td>
                            <td class="px-4 py-3">{{ $money($revision->revised_yearly) }} <span class="text-xs text-gray-500">({{ __('was :amount', ['amount' => $money($revision->previous_yearly)]) }})</span></td>
                            <td class="px-4 py-3 font-semibold {{ $pct >= 0 ? 'text-success' : 'text-red-600' }}">{{ $pct >= 0 ? '+' : '' }}{{ $pct }}%</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[$revision->status] }}">{{ __(ucfirst($revision->status)) }}</span></td>
                            <td class="px-4 py-3">
                                @if ($revision->status === 'pending')
                                    <div class="flex gap-1">
                                        <form method="POST" action="{{ route('payroll.revisions.apply', $revision) }}" data-confirm="{{ __('Apply this revision now?') }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="inline-flex h-7 items-center justify-center gap-1 whitespace-nowrap rounded-control border border-line-strong bg-surface px-2.5 text-xs font-medium text-primary-ink hover:border-primary/40 hover:bg-primary-soft/60">{{ __('Apply now') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('payroll.revisions.cancel', $revision) }}" data-confirm="{{ __('Cancel this revision?') }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-danger-soft">{{ __('Cancel') }}</button>
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
