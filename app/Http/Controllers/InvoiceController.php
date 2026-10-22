<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\Invoice\PayrollInvoiceLines;
use Carbon\CarbonImmutable;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Invoice (admin only, config/access.php): tax invoices to client
 * companies, pre-filled from the company's salary sheet for the month.
 */
class InvoiceController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        $thisMonth = Invoice::whereBetween('invoice_date', [now()->startOfMonth(), now()->endOfMonth()]);

        return view('invoice.index', [
            'stats' => [
                'count' => Invoice::count(),
                'this_month' => (clone $thisMonth)->count(),
                'this_month_total' => (float) $thisMonth->sum('grand_total'),
                'total' => (float) Invoice::sum('grand_total'),
            ],
            'invoices' => Invoice::with('company')
                ->tap(fn (Builder $q) => $this->search($q, $request, ['invoice_no', 'customer_name', 'company.establishment_name']))
                ->tap(fn (Builder $q) => $this->sort($q, $request, ['no' => 'invoice_no', 'date' => 'invoice_date', 'customer' => 'customer_name', 'total' => 'grand_total'],
                    fn (Builder $q) => $q->latest('invoice_date')->latest('id')))
                ->paginate($perPage = $this->perPage($request))->withQueryString(),
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View
    {
        $invoice = new Invoice([
            'invoice_date' => now(), 'lines' => [], 'extra_lines' => [],
            'service_charge_rate' => config('invoice.service_charge_rate'), 'gst_rate' => config('invoice.gst_rate'),
        ]);
        $sheet = null;
        $missing = false;

        $company = $request->filled('company') ? Company::where('uuid', $request->query('company'))->first() : null;
        $month = rescue(fn () => CarbonImmutable::createFromFormat('!Y-m', (string) $request->query('month')), null, false);

        if ($company) {
            $last = Invoice::where('company_id', $company->id)->latest('invoice_date')->latest('id')->first();
            $invoice->fill([
                'company_id' => $company->id,
                'customer_name' => $last->customer_name ?? $company->establishment_name,
                'customer_address' => $last->customer_address ?? collect([$company->address_line_1, $company->address_line_2, $company->city, $company->village, $company->taluka, $company->district, $company->state, $company->country])->filter()->implode(",\n"),
                'customer_gst' => $last?->customer_gst,
            ]);

            if ($month) {
                $invoice->month = $month;
                if ($prefill = PayrollInvoiceLines::for($company, $month)) {
                    $invoice->lines = $prefill['lines'];
                    $invoice->extra_lines = $prefill['extra_lines'];
                    $sheet = $prefill['sheet'];
                } else {
                    $missing = true;
                }
            }
        }

        return view('invoice.form', $this->formData($invoice) + ['sheet' => $sheet, 'missing' => $missing, 'month' => $month]);
    }

    public function store(Request $request): RedirectResponse
    {
        $invoice = new Invoice($this->validated($request) + ['created_by' => $request->user()->id]);
        $invoice->grand_total = $invoice->totals()['grand_total'];
        $invoice->save();

        return redirect()->route('invoice.show', $invoice)->with('status', __('Invoice :no saved.', ['no' => $invoice->invoice_no]));
    }

    public function show(Invoice $invoice): View
    {
        return view('invoice.show', ['invoice' => $invoice, 'totals' => $invoice->totals()]);
    }

    /** The invoice as a PDF file on the Srympex letterhead. */
    public function pdf(Invoice $invoice): Response
    {
        $dompdf = new Dompdf((new Options)->set('isRemoteEnabled', false)->set('defaultFont', 'Helvetica'));
        $dompdf->loadHtml(view('invoice.pdf', ['invoice' => $invoice, 'totals' => $invoice->totals()])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.Str::slug($invoice->invoice_no).'-'.Str::slug($invoice->customer_name).'.pdf"',
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        return view('invoice.form', $this->formData($invoice) + ['sheet' => null, 'missing' => false, 'month' => null]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->fill($this->validated($request, $invoice));
        $invoice->grand_total = $invoice->totals()['grand_total'];
        $invoice->save();

        return redirect()->route('invoice.show', $invoice)->with('status', __('Invoice :no updated.', ['no' => $invoice->invoice_no]));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('invoice.index')->with('status', __('Invoice :no deleted.', ['no' => $invoice->invoice_no]))
            ->with('undo', \App\Support\RecentlyDeleted::restoreUrl($invoice));
    }

    private function formData(Invoice $invoice): array
    {
        return [
            'invoice' => $invoice,
            'companies' => Company::orderBy('establishment_name')->get(),
            'lastNo' => Invoice::latest('id')->value('invoice_no'),
        ];
    }

    private function validated(Request $request, ?Invoice $invoice = null): array
    {
        // Rows left completely empty are ignored.
        foreach (['lines', 'extra_lines'] as $list) {
            $request->merge([$list => collect($request->input($list, []))
                ->reject(fn ($line) => blank($line['description'] ?? null) && blank($line['amount'] ?? null))->values()->all()]);
        }

        $data = $request->validate([
            'company' => ['required', 'uuid', Rule::exists('companies', 'uuid')->whereNull('deleted_at')],
            'invoice_no' => ['required', 'string', 'max:50', Rule::unique('invoices', 'invoice_no')->whereNull('deleted_at')->ignore($invoice?->id)],
            'invoice_date' => ['required', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'customer_gst' => ['nullable', 'string', 'max:20'],
            'lines' => ['required', 'array', 'min:1', 'max:30'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'numeric', 'min:-9999999999', 'max:9999999999'],
            'extra_lines' => ['array', 'max:30'],
            'extra_lines.*.description' => ['required', 'string', 'max:255'],
            'extra_lines.*.amount' => ['required', 'numeric', 'min:-9999999999', 'max:9999999999'],
            'service_charge_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [], [
            'invoice_no' => 'invoice number', 'customer_gst' => 'customer GST number', 'lines' => 'charges',
            'lines.*.description' => 'description', 'lines.*.amount' => 'amount',
            'extra_lines.*.description' => 'description', 'extra_lines.*.amount' => 'amount',
        ]);

        $line = fn (array $l) => ['description' => trim($l['description']), 'amount' => round((float) $l['amount'], 2)];

        return [
            'company_id' => Company::where('uuid', $data['company'])->value('id'),
            'invoice_no' => trim($data['invoice_no']),
            'invoice_date' => $data['invoice_date'],
            'month' => filled($data['month'] ?? null) ? $data['month'].'-01' : null,
            'customer_name' => trim($data['customer_name']),
            'customer_address' => filled($data['customer_address'] ?? null) ? trim($data['customer_address']) : null,
            'customer_gst' => filled($data['customer_gst'] ?? null) ? strtoupper(trim($data['customer_gst'])) : null,
            'lines' => array_map($line, $data['lines']),
            'extra_lines' => array_map($line, $data['extra_lines'] ?? []),
            'service_charge_rate' => $data['service_charge_rate'],
            'gst_rate' => $data['gst_rate'],
        ];
    }
}
