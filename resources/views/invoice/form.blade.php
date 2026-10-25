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
        $input = 'control px-3';
        $back = $editing ? route('invoice.show', $invoice) : route('invoice.index');
    @endphp

    <x-ui.page-header :title="$editing ? __('Edit invoice :no', ['no' => $invoice->invoice_no]) : __('New invoice')"
                      :crumbs="[['label' => __('Invoices'), 'url' => route('invoice.index')]]"
                      :description="__('Pick the customer and month, bring in the charges from payroll, then check every amount before saving.')" />

    <x-ui.error-summary />

    <form method="POST" action="{{ $editing ? route('invoice.update', $invoice) : route('invoice.store') }}" class="space-y-6" data-unsaved-warning
          x-data="invoiceForm(@js($state))">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card px-5 py-7 sm:px-6">
        <x-ui.form-section :title="__('Customer and month')" :description="__('The company you are billing and the month of work.')" :columns="3">
                <x-select-field name="company" :label="__('Company')" required :placeholder="__('Choose a company')" x-model="company"
                                :options="$companies->pluck('establishment_name', 'uuid')->all()" :value="$state['company']" />
                <x-field name="month" type="month" :label="__('Month billed')" x-model="month" :value="$state['month']" />
                @unless ($editing)
                    <div class="flex items-end">
                        <button type="button" x-on:click="fill()" :disabled="! company || ! month"
                                class="inline-flex h-control w-full items-center justify-center gap-2 rounded-control border border-primary/40 bg-primary-soft px-3 text-sm font-medium text-primary-ink hover:bg-primary-soft/70 disabled:cursor-not-allowed disabled:opacity-50">
                            <x-icon name="download" class="size-4" />{{ __('Fill from payroll') }}
                        </button>
                    </div>
                @endunless
            <div class="sm:col-span-2 xl:col-span-3">
            @if ($sheet)
                <p class="flex gap-2 rounded-control border border-success/25 bg-success-soft px-3 py-2 text-13 text-success"><x-icon name="check-circle" class="size-4 shrink-0" /><span>
                    {{ __('Filled from the salary sheet for :month (:count people). Check the amounts and change anything you need.', ['month' => $sheet->month->format('F Y'), 'count' => $sheet->payslips()->count()]) }}
                    @unless ($sheet->isApproved()) <strong>{{ __('This salary is not approved yet.') }}</strong> @endunless
                </span></p>
            @elseif ($missing)
                <p class="rounded-control border border-warn/30 bg-warn-soft px-3 py-2 text-13 text-warn">{{ __('No salary has been generated for this company for :month. Generate it in Payroll first, or type the amounts below.', ['month' => $month->format('F Y')]) }}</p>
            @elseif (! $editing)
                <p class="text-13 text-ink-2">{{ __('Choose the company and month, then “Fill from payroll” to bring in the charges from that month’s salary.') }}</p>
            @endif
            </div>
        </x-ui.form-section>

        <x-ui.form-section :title="__('Invoice details')" :description="__('What prints at the top of the invoice.')">
                <x-field name="invoice_no" :label="__('Invoice no')" required :value="$invoice->invoice_no"
                         :hint="$lastNo ? __('Last invoice: :no', ['no' => $lastNo]) : __('For example 26/27-199')" />
                <x-field name="invoice_date" type="date" :label="__('Date')" required :value="$invoice->invoice_date" />
                <x-field name="customer_name" :label="__('Customer name')" required :value="$invoice->customer_name" />
                <x-field name="customer_gst" :label="__('Customer GST no')" x-model="gst" :value="$state['gst']" />
                <div class="sm:col-span-2">
                    <x-ui.field :label="__('Customer address')" for="field-customer-address" name="customer_address">
                        <textarea id="field-customer-address" name="customer_address" rows="4" class="control px-3 py-2" placeholder="{{ __('Street, city, PIN code') }}">{{ old('customer_address', $invoice->customer_address) }}</textarea>
                    </x-ui.field>
                </div>
        </x-ui.form-section>
        </div>

        <section class="card">
            <div class="border-b border-line px-5 py-4 sm:px-6"><h2 class="text-[15px] font-semibold text-ink">{{ __('Charges') }}</h2><p class="mt-0.5 text-13 text-ink-2">{{ __('Totals and GST are worked out as you type.') }}</p></div>
            <x-input-error class="px-5 pt-2" :messages="$errors->get('lines')" />
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-line text-left text-xs font-medium text-ink-2">
                        <tr>
                            <th class="w-12 px-4 py-2.5">{{ __('No') }}</th>
                            <th class="px-4 py-2.5">{{ __('Description') }}</th>
                            <th class="w-48 px-4 py-2.5 text-right">{{ __('Amount (₹)') }}</th>
                            <th class="w-12 px-2 py-2.5"><span class="sr-only">{{ __('Remove') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="(line, i) in lines" :key="i">
                            <tr>
                                <td class="px-4 py-2 font-mono text-ink-3" x-text="i + 1"></td>
                                <td class="px-4 py-2"><input type="text" :name="`lines[${i}][description]`" x-model="line.description" class="{{ $input }}" placeholder="{{ __('e.g. Manpower Supply Charges Aug 26') }}" aria-label="{{ __('Description') }}"></td>
                                <td class="px-4 py-2"><input type="number" step="0.01" :name="`lines[${i}][amount]`" x-model="line.amount" class="{{ $input }} text-right tabular-nums" aria-label="{{ __('Amount') }}"></td>
                                <td class="px-2 py-2"><button type="button" x-on:click="lines.splice(i, 1)" x-show="lines.length > 1" class="rounded-md p-2 text-ink-3 hover:bg-sunken hover:text-danger"><x-icon name="x" class="h-4 w-4" /><span class="sr-only">{{ __('Remove') }}</span></button></td>
                            </tr>
                        </template>
                        <tr><td></td><td colspan="3" class="px-4 py-2"><button type="button" x-on:click="lines.push({ description: '', amount: '' })" class="text-13 font-medium text-primary hover:underline">+ {{ __('Add a line') }}</button></td></tr>
                        <tr class="bg-sunken/60 font-semibold">
                            <td></td><td class="px-4 py-2.5 text-right">{{ __('Sub Total') }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums" x-text="money(subTotal())"></td><td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="px-4 py-2 text-right">
                                <label class="inline-flex items-center gap-2 whitespace-nowrap font-semibold">{{ __('Service Charges @') }}
                                    <input type="number" step="0.01" min="0" max="100" name="service_charge_rate" x-model="serviceRate" class="control-inline w-20 px-2 text-right" aria-label="{{ __('Service charge percent') }}">%
                                </label>
                                <x-input-error class="mt-1" :messages="$errors->get('service_charge_rate')" />
                            </td>
                            <td class="px-4 py-2 text-right font-semibold tabular-nums" x-text="money(serviceCharge())"></td><td></td>
                        </tr>
                        <tr><td></td><td colspan="3" class="px-4 pb-1 pt-5 text-13 font-medium text-ink-2">{{ __('Added after the service charge (OT, arrears, food…)') }}</td></tr>
                        <template x-for="(line, i) in extras" :key="'x' + i">
                            <tr>
                                <td class="px-4 py-2 font-mono text-ink-3" x-text="lines.length + i + 1"></td>
                                <td class="px-4 py-2"><input type="text" :name="`extra_lines[${i}][description]`" x-model="line.description" class="{{ $input }}" placeholder="{{ __('e.g. Food Expense') }}" aria-label="{{ __('Description') }}"></td>
                                <td class="px-4 py-2"><input type="number" step="0.01" :name="`extra_lines[${i}][amount]`" x-model="line.amount" class="{{ $input }} text-right tabular-nums" aria-label="{{ __('Amount') }}"></td>
                                <td class="px-2 py-2"><button type="button" x-on:click="extras.splice(i, 1)" class="rounded-md p-2 text-ink-3 hover:bg-sunken hover:text-danger"><x-icon name="x" class="h-4 w-4" /><span class="sr-only">{{ __('Remove') }}</span></button></td>
                            </tr>
                        </template>
                        <tr><td></td><td colspan="3" class="px-4 py-2"><button type="button" x-on:click="extras.push({ description: '', amount: '' })" class="text-13 font-medium text-primary hover:underline">+ {{ __('Add a line') }}</button></td></tr>
                        <tr class="bg-sunken/60 font-semibold">
                            <td></td><td class="px-4 py-2.5 text-right">{{ __('Total') }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums" x-text="money(total())"></td><td></td>
                        </tr>
                        <template x-for="tax in taxes()" :key="tax.label">
                            <tr>
                                <td></td><td class="px-4 py-2 text-right" x-text="tax.label"></td>
                                <td class="px-4 py-2 text-right tabular-nums" x-text="money(tax.amount)"></td><td></td>
                            </tr>
                        </template>
                        <tr class="border-t-2 border-ink/80 text-base font-semibold">
                            <td></td><td class="px-4 py-3 text-right">{{ __('Grand Total') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums" x-text="money(grandTotal())"></td><td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center gap-3 border-t border-line px-5 py-3 text-13 text-ink-2">
                <label class="inline-flex items-center gap-2 whitespace-nowrap">{{ __('GST rate') }}
                    <input type="number" step="0.01" min="0" max="100" name="gst_rate" x-model="gstRate" class="control-inline w-20 px-2 text-right">%
                </label>
                <span x-text="intraState() ? @js(__('Customer is in the same state: CGST + SGST.')) : @js(__('Customer is in another state (or no GST no): IGST.'))"></span>
            </div>
        </section>

        <x-ui.save-bar>
            <x-slot:status><span x-text="@js(__('Grand Total')) + ': ₹ ' + money(grandTotal())"></span></x-slot:status>
            <x-ui.button variant="ghost" :href="$back" data-discard>{{ __('Cancel') }}</x-ui.button>
            <x-ui.button>{{ $editing ? __('Save changes') : __('Save invoice') }}</x-ui.button>
        </x-ui.save-bar>
    </form>
</x-app-layout>
