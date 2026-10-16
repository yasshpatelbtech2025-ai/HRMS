<x-app-layout>
    <x-slot name="header">{{ __('Invoice') }}</x-slot>

    @php
        $editing = $invoice->exists;
        $rows = fn (string $list) => collect(old($list, $invoice->{$list} ?: []))->map(fn ($l) => ['description' => $l['description'] ?? '', 'amount' => $l['amount'] ?? ''])->values();
        $state = [
            'company' => old('company', $invoice->company?->uuid ?? $companies->firstWhere('id', $invoice->company_id)?->uuid ?? ''),
            'month' => old('month', $invoice->month?->format('Y-m') ?? ''),
            'gst' => old('customer_gst', $invoice->customer_gst ?? ''),
            'lines' => $rows('lines')->whenEmpty(fn ($c) => $c->push(['description' => '', 'amount' => ''])),
            'extras' => $rows('extra_lines'),
            'serviceRate' => old('service_charge_rate', (float) $invoice->service_charge_rate),
            'gstRate' => old('gst_rate', (float) $invoice->gst_rate),
            'stateCode' => config('invoice.issuer.state_code'),
            'createUrl' => route('invoice.create'),
        ];
        $input = 'block w-full rounded-md border-line-strong bg-surface text-sm text-ink shadow-none placeholder:text-ink-3 focus:border-primary focus:ring-2 focus:ring-primary/20';
    @endphp

    <div class="mb-4">
        <a href="{{ $editing ? route('invoice.show', $invoice) : route('invoice.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ $editing ? __('Back to invoice') : __('Back to invoices') }}</a>
        <h2 class="mt-1 text-2xl font-bold text-gray-900">{{ $editing ? __('Edit invoice :no', ['no' => $invoice->invoice_no]) : __('New invoice') }}</h2>
    </div>

    <form method="POST" action="{{ $editing ? route('invoice.update', $invoice) : route('invoice.store') }}" class="space-y-5"
          x-data="invoiceForm(@js($state))">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <h3 class="mb-4 text-sm font-semibold text-ink">{{ __('Customer and month') }}</h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-select-field name="company" :label="__('Company')" required :placeholder="__('Choose a company')" x-model="company"
                                :options="$companies->pluck('establishment_name', 'uuid')->all()" :value="$state['company']" />
                <x-field name="month" type="month" :label="__('Month billed')" x-model="month" :value="$state['month']" />
                @unless ($editing)
                    <div class="flex items-end">
                        <button type="button" x-on:click="fill()" :disabled="! company || ! month"
                                class="h-[38px] w-full rounded-md border border-line-strong bg-surface px-3 text-sm font-medium text-ink hover:bg-sunken disabled:cursor-not-allowed disabled:opacity-50">
                            {{ __('Fill from payroll') }}
                        </button>
                    </div>
                @endunless
            </div>
            @if ($sheet)
                <p class="mt-3 rounded-md bg-success-soft px-3 py-2 text-13 text-success">
                    {{ __('Filled from the salary sheet for :month (:count people). Check the amounts and change anything you need.', ['month' => $sheet->month->format('F Y'), 'count' => $sheet->payslips()->count()]) }}
                    @unless ($sheet->isApproved()) <strong>{{ __('This salary is not approved yet.') }}</strong> @endunless
                </p>
            @elseif ($missing)
                <p class="mt-3 rounded-md bg-warn-soft px-3 py-2 text-13 text-warn">{{ __('No salary has been generated for this company for :month. Generate it in Payroll first, or type the amounts below.', ['month' => $month->format('F Y')]) }}</p>
            @elseif (! $editing)
                <p class="mt-3 text-xs text-ink-3">{{ __('Choose the company and month, then “Fill from payroll” to bring in the charges from that month’s salary.') }}</p>
            @endif
        </section>

        <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <h3 class="mb-4 text-sm font-semibold text-ink">{{ __('Invoice details') }}</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="invoice_no" :label="__('Invoice no')" required :value="$invoice->invoice_no"
                         :hint="$lastNo ? __('Last invoice: :no', ['no' => $lastNo]) : __('For example 26/27-199')" />
                <x-field name="invoice_date" type="date" :label="__('Date')" required :value="$invoice->invoice_date" />
                <x-field name="customer_name" :label="__('Customer name')" required :value="$invoice->customer_name" />
                <x-field name="customer_gst" :label="__('Customer GST no')" x-model="gst" :value="$state['gst']" />
                <div class="sm:col-span-2">
                    <label for="field-customer-address" class="block text-13 font-medium text-ink-2">{{ __('Customer address') }}</label>
                    <textarea id="field-customer-address" name="customer_address" rows="4" class="mt-1.5 {{ $input }}" placeholder="{{ __('Customer address') }}">{{ old('customer_address', $invoice->customer_address) }}</textarea>
                    <x-input-error class="mt-1" :messages="$errors->get('customer_address')" />
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
            <h3 class="px-5 pt-5 text-sm font-semibold text-ink">{{ __('Charges') }}</h3>
            <x-input-error class="px-5 pt-2" :messages="$errors->get('lines')" />
            <div class="overflow-x-auto">
                <table class="mt-3 min-w-full text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-left font-semibold text-gray-700">
                        <tr>
                            <th class="w-12 px-4 py-2.5">{{ __('No') }}</th>
                            <th class="px-4 py-2.5">{{ __('Description') }}</th>
                            <th class="w-48 px-4 py-2.5 text-right">{{ __('Amount (₹)') }}</th>
                            <th class="w-12 px-2 py-2.5"><span class="sr-only">{{ __('Remove') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(line, i) in lines" :key="i">
                            <tr>
                                <td class="px-4 py-2 text-gray-500" x-text="i + 1"></td>
                                <td class="px-4 py-2"><input type="text" :name="`lines[${i}][description]`" x-model="line.description" class="{{ $input }}" placeholder="{{ __('e.g. Manpower Supply Charges Aug 26') }}" aria-label="{{ __('Description') }}"></td>
                                <td class="px-4 py-2"><input type="number" step="0.01" :name="`lines[${i}][amount]`" x-model="line.amount" class="{{ $input }} text-right tabular-nums" aria-label="{{ __('Amount') }}"></td>
                                <td class="px-2 py-2"><button type="button" x-on:click="lines.splice(i, 1)" x-show="lines.length > 1" class="rounded-md p-2 text-ink-3 hover:bg-sunken hover:text-danger"><x-icon name="x" class="h-4 w-4" /><span class="sr-only">{{ __('Remove') }}</span></button></td>
                            </tr>
                        </template>
                        <tr><td></td><td colspan="3" class="px-4 py-2"><button type="button" x-on:click="lines.push({ description: '', amount: '' })" class="text-13 font-medium text-primary hover:underline">+ {{ __('Add a line') }}</button></td></tr>
                        <tr class="bg-gray-50 font-semibold">
                            <td></td><td class="px-4 py-2.5 text-right">{{ __('Sub Total') }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums" x-text="money(subTotal())"></td><td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="px-4 py-2 text-right">
                                <label class="inline-flex items-center gap-2 whitespace-nowrap font-semibold">{{ __('Service Charges @') }}
                                    <input type="number" step="0.01" min="0" max="100" name="service_charge_rate" x-model="serviceRate" class="{{ $input }} w-20 text-right" aria-label="{{ __('Service charge percent') }}">%
                                </label>
                                <x-input-error class="mt-1" :messages="$errors->get('service_charge_rate')" />
                            </td>
                            <td class="px-4 py-2 text-right font-semibold tabular-nums" x-text="money(serviceCharge())"></td><td></td>
                        </tr>
                        <tr><td></td><td colspan="3" class="px-4 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-ink-3">{{ __('Added after the service charge (OT, arrears, food…)') }}</td></tr>
                        <template x-for="(line, i) in extras" :key="'x' + i">
                            <tr>
                                <td class="px-4 py-2 text-gray-500" x-text="lines.length + i + 1"></td>
                                <td class="px-4 py-2"><input type="text" :name="`extra_lines[${i}][description]`" x-model="line.description" class="{{ $input }}" placeholder="{{ __('e.g. Food Expense') }}" aria-label="{{ __('Description') }}"></td>
                                <td class="px-4 py-2"><input type="number" step="0.01" :name="`extra_lines[${i}][amount]`" x-model="line.amount" class="{{ $input }} text-right tabular-nums" aria-label="{{ __('Amount') }}"></td>
                                <td class="px-2 py-2"><button type="button" x-on:click="extras.splice(i, 1)" class="rounded-md p-2 text-ink-3 hover:bg-sunken hover:text-danger"><x-icon name="x" class="h-4 w-4" /><span class="sr-only">{{ __('Remove') }}</span></button></td>
                            </tr>
                        </template>
                        <tr><td></td><td colspan="3" class="px-4 py-2"><button type="button" x-on:click="extras.push({ description: '', amount: '' })" class="text-13 font-medium text-primary hover:underline">+ {{ __('Add a line') }}</button></td></tr>
                        <tr class="bg-gray-50 font-semibold">
                            <td></td><td class="px-4 py-2.5 text-right">{{ __('Total') }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums" x-text="money(total())"></td><td></td>
                        </tr>
                        <template x-for="tax in taxes()" :key="tax.label">
                            <tr>
                                <td></td><td class="px-4 py-2 text-right" x-text="tax.label"></td>
                                <td class="px-4 py-2 text-right tabular-nums" x-text="money(tax.amount)"></td><td></td>
                            </tr>
                        </template>
                        <tr class="border-t border-gray-200 text-base font-bold">
                            <td></td><td class="px-4 py-3 text-right">{{ __('Grand Total') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums" x-text="money(grandTotal())"></td><td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 px-5 py-3 text-13 text-ink-3">
                <label class="inline-flex items-center gap-2 whitespace-nowrap">{{ __('GST rate') }}
                    <input type="number" step="0.01" min="0" max="100" name="gst_rate" x-model="gstRate" class="{{ $input }} w-20 text-right">%
                </label>
                <span x-text="intraState() ? @js(__('Customer is in the same state: CGST + SGST.')) : @js(__('Customer is in another state (or no GST no): IGST.'))"></span>
            </div>
        </section>

        <div class="flex justify-end gap-2">
            <a href="{{ $editing ? route('invoice.show', $invoice) : route('invoice.index') }}" class="inline-flex h-9 items-center rounded-md border border-line-strong bg-surface px-3.5 text-sm font-medium text-ink hover:bg-sunken">{{ __('Cancel') }}</a>
            <button class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-white hover:bg-primary-hover">{{ $editing ? __('Save changes') : __('Save invoice') }}</button>
        </div>
    </form>
</x-app-layout>
