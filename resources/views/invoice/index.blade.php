<x-app-layout>
    <x-slot name="header">{{ __('Invoice') }}</x-slot>

    @php $money = fn ($n) => '₹'.\App\Support\IndianNumber::format((float) $n); @endphp

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Invoices') }}</h1>
            <a href="{{ route('invoice.create') }}" class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-3.5 text-sm font-medium text-white shadow-hairline hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-1">
                <x-icon name="plus" class="h-4 w-4" />{{ __('New invoice') }}
            </a>
        </div>
        <x-stat-strip :items="[
            ['label' => __('Invoices'), 'value' => $stats['count']],
            ['label' => __('This month'), 'value' => $stats['this_month']],
            ['label' => __('Billed this month'), 'value' => $money($stats['this_month_total'])],
            ['label' => __('Billed in total'), 'value' => $money($stats['total'])],
        ]" />

        <div class="card">
            <x-list-toolbar :action="route('invoice.index')" :per-page="$perPage" />

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                        <tr>
                            <x-sort-header column="no" class="px-4 py-3">{{ __('Invoice no') }}</x-sort-header>
                            <x-sort-header column="date" class="px-4 py-3">{{ __('Date') }}</x-sort-header>
                            <x-sort-header column="customer" class="px-4 py-3">{{ __('Customer') }}</x-sort-header>
                            <th class="px-4 py-3">{{ __('Month') }}</th>
                            <x-sort-header column="total" class="px-4 py-3 text-right">{{ __('Grand total') }}</x-sort-header>
                            <th class="px-4 py-3">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($invoices as $invoice)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium"><a href="{{ route('invoice.show', $invoice) }}" class="text-primary hover:underline">{{ $invoice->invoice_no }}</a></td>
                                <td class="px-4 py-3">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                                <td class="px-4 py-3 text-gray-900">{{ $invoice->customer_name }}
                                    @if ($invoice->company && $invoice->company->establishment_name !== $invoice->customer_name)
                                        <span class="block text-xs text-gray-500">{{ $invoice->company->establishment_name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $invoice->month?->format('M Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $money($invoice->grand_total) }}</td>
                                <td class="px-4 py-3">
                                    <x-row-actions :delete-url="route('invoice.destroy', $invoice)" :delete-confirm="__('Delete invoice :no?', ['no' => $invoice->invoice_no])">
                                        <a href="{{ route('invoice.show', $invoice) }}" role="menuitem" class="rounded-md p-2"><x-icon name="document" class="h-4 w-4" /><span class="sr-only">{{ __('View / print') }}</span></a>
                                        <a href="{{ route('invoice.pdf', $invoice) }}" role="menuitem" class="rounded-md p-2"><x-icon name="arrow-down" class="h-4 w-4" /><span class="sr-only">{{ __('Download PDF') }}</span></a>
                                        <a href="{{ route('invoice.edit', $invoice) }}" role="menuitem" class="rounded-md p-2"><x-icon name="pencil" class="h-4 w-4" /><span class="sr-only">{{ __('Edit') }}</span></a>
                                    </x-row-actions>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6" icon="inbox">{{ __('No invoices yet.') }}</x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-list-footer :paginator="$invoices" />
        </div>
    </div>
</x-app-layout>
