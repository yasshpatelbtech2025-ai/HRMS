{{--
    Add / edit loan. Repayment amount (principal + flat interest) and the
    installment are filled in as the amount, rate and period change.
    Expects: $loan, $modal, $employees (with company).
--}}
@php
    $editing = $loan->exists;
    $mine = old('_modal') === $modal;
    $value = fn (string $field, $model) => $mine ? old($field) : $model;
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $input = 'control';
@endphp

<form method="POST" action="{{ $editing ? route('payroll.loans.update', $loan) : route('payroll.loans.store') }}" enctype="multipart/form-data"
      x-data="{
          employee: @js((string) $value('employee', $loan->employee?->uuid)),
          employees: @js($employees->map(fn ($e) => ['uuid' => $e->uuid, 'company_id' => $e->company_id, 'label' => $e->employee_code.' - '.$e->full_name])),
          amount: @js($value('amount', $loan->amount)), rate: @js($value('interest_rate', $loan->interest_rate)), period: @js($value('installment_period', $loan->installment_period)),
          repayment: @js($value('repayment_amount', $loan->repayment_amount)), installment: @js($value('installment', $loan->installment)),
          get colleagues() {
              const company = this.employees.find(e => e.uuid === this.employee)?.company_id;
              return this.employees.filter(e => e.company_id === company && e.uuid !== this.employee);
          },
          calculate() {
              const amount = Number(this.amount) || 0, rate = Number(this.rate) || 0, period = Number(this.period) || 0;
              this.repayment = Math.round(amount + amount * rate / 100);
              this.installment = period > 0 ? Math.round(this.repayment / period) : this.repayment;
          },
      }">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">
    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editing ? __('Edit loan :no', ['no' => $loan->loan_no]) : __('Add new loan') }}</h2>
    </div>
    <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
        <x-form-row :label="__('Employee name')" for="{{ $modal }}-employee" required :messages="$error('employee')">
            <select id="{{ $modal }}-employee" name="employee" x-model="employee" required class="{{ $input }}">
                <option value="">{{ __('Select employee') }}</option>
                <template x-for="e in employees" :key="e.uuid"><option :value="e.uuid" x-text="e.label" :selected="e.uuid === employee"></option></template>
            </select>
        </x-form-row>
        <x-form-row :label="__('Permitted by')" for="{{ $modal }}-permitted" required :messages="$error('permitted_by')">
            <select id="{{ $modal }}-permitted" name="permitted_by" required class="{{ $input }}" :disabled="! employee">
                <option value="">{{ __('Select supervisor') }}</option>
                <template x-for="e in colleagues" :key="e.uuid"><option :value="e.uuid" x-text="e.label" :selected="e.uuid === @js((string) $value('permitted_by', $loan->permittedBy?->uuid))"></option></template>
            </select>
        </x-form-row>
        <x-form-row :label="__('Loan details')" for="{{ $modal }}-details">
            <textarea id="{{ $modal }}-details" name="details" rows="2" class="{{ $input }}">{{ $value('details', $loan->details) }}</textarea>
        </x-form-row>
        <x-form-row :label="__('Amount')" for="{{ $modal }}-amount" required :messages="$error('amount')">
            <input type="number" step="0.01" min="1" id="{{ $modal }}-amount" name="amount" x-model="amount" x-on:input="calculate()" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Approved date')" for="{{ $modal }}-approved" required :messages="$error('approved_date')">
            <input type="date" id="{{ $modal }}-approved" name="approved_date" value="{{ $value('approved_date', $loan->approved_date?->toDateString()) }}" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Repayment from')" for="{{ $modal }}-start" required :messages="$error('repayment_start')">
            <input type="month" id="{{ $modal }}-start" name="repayment_start" value="{{ $value('repayment_start', $loan->repayment_start?->format('Y-m')) }}" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Interest percentage(%)')" for="{{ $modal }}-rate" required :messages="$error('interest_rate')">
            <input type="number" step="0.01" min="0" max="100" id="{{ $modal }}-rate" name="interest_rate" x-model="rate" x-on:input="calculate()" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Installment period')" for="{{ $modal }}-period" required :messages="$error('installment_period')">
            <input type="number" min="1" id="{{ $modal }}-period" name="installment_period" x-model="period" x-on:input="calculate()" required class="{{ $input }}">
            <p class="mt-1 text-xs text-gray-500">{{ __('Number of monthly installments.') }}</p>
        </x-form-row>
        <x-form-row :label="__('Repayment amount')" for="{{ $modal }}-repayment" required :messages="$error('repayment_amount')">
            <input type="number" step="0.01" min="0" id="{{ $modal }}-repayment" name="repayment_amount" x-model="repayment" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Installment')" for="{{ $modal }}-installment" required :messages="$error('installment')">
            <input type="number" step="0.01" min="0" id="{{ $modal }}-installment" name="installment" x-model="installment" required class="{{ $input }}">
        </x-form-row>
        @if ($editing)
            <x-form-row :label="__('Installment cleared')" for="{{ $modal }}-cleared" :messages="$error('installments_cleared')">
                <input type="number" min="0" id="{{ $modal }}-cleared" name="installments_cleared" value="{{ $value('installments_cleared', $loan->installments_cleared) }}" class="{{ $input }}">
            </x-form-row>
        @endif
        <x-form-row :label="__('Status')" for="{{ $modal }}-status" required>
            <select id="{{ $modal }}-status" name="is_active" class="{{ $input }}">
                <option value="1" @selected((string) $value('is_active', (int) $loan->is_active) !== '0')>{{ __('Active') }}</option>
                <option value="0" @selected((string) $value('is_active', (int) $loan->is_active) === '0')>{{ __('Inactive') }}</option>
            </select>
        </x-form-row>
        <x-form-row :label="__('Attachment')" for="{{ $modal }}-attachment" :messages="$error('attachment')">
            <input type="file" id="{{ $modal }}-attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium">
        </x-form-row>
    </div>
    <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
        <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
