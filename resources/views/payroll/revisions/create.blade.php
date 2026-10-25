<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php
        $labels = config('employee.gross_elements') + config('employee.deduction_elements');
        $allowed = $newStructure ? ['basic', 'allowance', 'bonus', ...array_keys(config('employee.deduction_elements'))] : array_keys($labels);
        $cell = 'control';
        $rows = collect($labels)->only($allowed)->map(fn ($label, $key) => [
            'label' => $label,
            'current' => $current[$key]['monthly'] ?? 0,
            'monthly' => old("salary.{$key}.monthly", $current[$key]['monthly'] ?? ''),
            'yearly' => old("salary.{$key}.yearly", $key === 'bonus' ? '' : ($current[$key]['yearly'] ?? '')),
        ]);
    @endphp

    <div class="mb-4">
        <a href="{{ route('payroll.revisions', ['employee' => $employee->uuid]) }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Back to salary revision list') }}</a>
    </div>

    <form method="POST" action="{{ route('payroll.revisions.store', $employee) }}"
          x-data="{ rows: @js($rows), pct: '',
                    applyPct() { const p = Number(this.pct) || 0; Object.entries(this.rows).forEach(([k, r]) => { if (k === 'bonus' || k in @js(config('employee.deduction_elements'))) return; r.monthly = Math.round(r.current * (1 + p / 100) * 100) / 100; r.yearly = Math.round(r.monthly * 12 * 100) / 100; }) },
                    total(field) { return @js(config('employee.gross_salary_elements')).reduce((s, k) => s + (Number(this.rows[k]?.[field === 'current' ? 'current' : field]) || 0), 0) } }">
        @csrf
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('Add salary revision') }}</h1>
                <p class="text-sm text-gray-500">{{ $employee->employee_code }} · {{ $employee->full_name }} · {{ $employee->company?->establishment_name }}</p>
            </div>
            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">{{ __('Raise all gross elements by %') }}</span>
                <span class="flex gap-2">
                    <input type="number" step="0.01" x-model="pct" class="w-28 {{ $cell }}">
                    <button type="button" x-on:click="applyPct()" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Apply') }}</button>
                </span>
            </label>
        </div>
        <div class="card">

        <div class="grid grid-cols-1 gap-4 px-5 py-4 sm:grid-cols-2">
            <x-form-row :label="__('Effective from')" for="effective_from" required :messages="$errors->get('effective_from')" class="!block">
                <input type="month" id="effective_from" name="effective_from" value="{{ old('effective_from', today()->format('Y-m')) }}" required class="{{ $cell }}">
            </x-form-row>
            <x-form-row :label="__('Remarks')" for="remarks" class="!block">
                <input id="remarks" name="remarks" value="{{ old('remarks') }}" class="{{ $cell }}">
            </x-form-row>
        </div>

        <div class="overflow-x-auto px-5">
            <table class="min-w-full text-sm">
                <thead class="text-left">
                    <tr><th class="px-3 py-2">{{ __('Element') }}</th><th class="px-3 py-2">{{ __('Current (monthly)') }}</th><th class="px-3 py-2">{{ __('Revised monthly') }}</th><th class="px-3 py-2">{{ __('Revised yearly') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($rows as $key => $row)
                        <tr>
                            <td class="px-3 py-2 font-medium text-gray-700">{{ $row['label'] }} @if ($key === 'basic')<span class="text-danger" aria-hidden="true">*</span>@endif</td>
                            <td class="px-3 py-2 text-gray-500">{{ number_format((float) $row['current'], 2) }}</td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.01" min="0" name="salary[{{ $key }}][monthly]" x-model="rows['{{ $key }}'].monthly"
                                       @unless ($key === 'bonus') x-on:input="rows['{{ $key }}'].yearly = rows['{{ $key }}'].monthly === '' ? '' : Math.round(rows['{{ $key }}'].monthly * 12 * 100) / 100" @endunless
                                       class="{{ $cell }}" aria-label="{{ $row['label'] }} {{ __('revised monthly') }}">
                                <x-input-error class="mt-1" :messages="$errors->get('salary.'.$key.'.monthly')" />
                            </td>
                            <td class="px-3 py-2">
                                @unless ($key === 'bonus')
                                    <input type="number" step="0.01" min="0" name="salary[{{ $key }}][yearly]" x-model="rows['{{ $key }}'].yearly"
                                           x-on:input="rows['{{ $key }}'].monthly = rows['{{ $key }}'].yearly === '' ? '' : Math.round(rows['{{ $key }}'].yearly / 12 * 100) / 100"
                                           class="{{ $cell }}" aria-label="{{ $row['label'] }} {{ __('revised yearly') }}">
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50 font-semibold">
                        <td class="px-3 py-2">{{ __('Gross (monthly)') }}</td>
                        <td class="px-3 py-2" x-text="total('current').toFixed(2)"></td>
                        <td class="px-3 py-2" x-text="total('monthly').toFixed(2)"></td>
                        <td class="px-3 py-2" x-text="(total('monthly') * 12).toFixed(2)"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
            <a href="{{ route('payroll.revisions', ['employee' => $employee->uuid]) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
            <x-primary-button>{{ __('Save revision') }}</x-primary-button>
        </div>
    </div>
    </form>
</x-app-layout>
