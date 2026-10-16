<x-app-layout>
    <x-slot name="header">{{ __('Invoice') }}</x-slot>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('invoice.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Back to invoices') }}</a>
        <div class="flex gap-2">
            <a href="{{ route('invoice.edit', $invoice) }}" class="inline-flex h-9 items-center rounded-md border border-line-strong bg-surface px-3.5 text-sm font-medium text-ink hover:bg-sunken">{{ __('Edit') }}</a>
            <button type="button" onclick="window.print()" class="inline-flex h-9 items-center rounded-md border border-line-strong bg-surface px-3.5 text-sm font-medium text-ink hover:bg-sunken">{{ __('Print') }}</button>
            <a href="{{ route('invoice.pdf', $invoice) }}" class="inline-flex h-9 items-center gap-1.5 rounded-md bg-primary px-3.5 text-sm font-medium text-white hover:bg-primary-hover"><x-icon name="arrow-down" class="h-4 w-4" />{{ __('Download PDF') }}</a>
        </div>
    </div>

    <style>@page { size: A4; margin: 8mm; } @media print { body { background: #fff !important; } }</style>

    <article class="sheet mx-auto max-w-[210mm] bg-white p-8 shadow-sm ring-1 ring-gray-200 print:max-w-none print:p-0 print:shadow-none print:ring-0">
        @include('invoice._sheet', ['pdf' => false])
    </article>
</x-app-layout>
