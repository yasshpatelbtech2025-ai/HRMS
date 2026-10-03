<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Loan;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll › Loan: loans to employees with flat interest, repaid in monthly
 * installments from salary.
 */
class LoanController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        return view('payroll.loans', [
            'loans' => $this->filtered($request)->paginate($perPage = $this->perPage($request))->withQueryString(),
            'perPage' => $perPage,
            'employees' => Employee::current()->with('company')->orderBy('company_id')->orderBy('employee_code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $loan = DB::transaction(function () use ($data) {
            $next = (int) Loan::lockForUpdate()->max('id') + 1;

            return Loan::create($data + ['loan_no' => sprintf('LN-%04d', $next)]);
        });
        $this->storeAttachment($request, $loan);

        return back()->with('status', __('Loan :no added.', ['no' => $loan->loan_no]));
    }

    public function update(Request $request, Loan $loan): RedirectResponse
    {
        $loan->update($this->validated($request, $loan));
        $this->storeAttachment($request, $loan);

        return back()->with('status', __('Loan :no updated.', ['no' => $loan->loan_no]));
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        if ($loan->installments_cleared > 0) {
            return back()->with('error', __('Loan :no has repayments recorded. Mark it inactive instead.', ['no' => $loan->loan_no]));
        }

        if ($loan->attachment_path) {
            Storage::disk(config('filesystems.employee_files'))->delete($loan->attachment_path);
        }
        $loan->delete();

        return back()->with('status', __('Loan deleted.'));
    }

    public function attachment(Loan $loan): StreamedResponse
    {
        $disk = Storage::disk(config('filesystems.employee_files'));
        abort_unless($loan->attachment_path && $disk->exists($loan->attachment_path), 404);

        return $disk->response($loan->attachment_path);
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        return TableExport::download($format, 'loans',
            ['Sl', 'Company name', 'Employee name', 'Permitted by', 'Loan no', 'Amount', 'Interest rate', 'Installment period', 'Installment cleared', 'Repayment amount', 'Approved date', 'Repayment from', 'Status'],
            $this->filtered($request)->get()->map(fn (Loan $l, int $i) => [
                $i + 1, $l->employee->company?->establishment_name, $l->employee->full_name, $l->permittedBy?->full_name, $l->loan_no, $l->amount,
                $l->interest_rate, $l->installment_period, $l->installments_cleared, $l->repayment_amount, $l->approved_date->toDateString(),
                $l->repayment_start->format('Y-m'), $l->is_active ? 'Active' : 'Inactive',
            ]),
        );
    }

    private function filtered(Request $request): Builder
    {
        return Loan::with(['employee.company', 'permittedBy'])
            ->when($request->filled('employee'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('uuid', $request->query('employee'))))
            ->tap(fn (Builder $q) => $this->search($q, $request, ['loan_no', 'employee.first_name', 'employee.last_name', 'employee.employee_code']))
            ->latest('approved_date')->latest('id');
    }

    private function validated(Request $request, ?Loan $loan = null): array
    {
        $employee = Employee::where('uuid', $request->input('employee'))->first();

        $data = $request->validate([
            'employee' => ['required', 'uuid', Rule::exists('employees', 'uuid')->whereNull('deleted_at')],
            'permitted_by' => ['required', 'uuid', Rule::exists('employees', 'uuid')->where('company_id', $employee?->company_id)->whereNull('deleted_at')],
            'details' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'approved_date' => ['required', 'date'],
            'repayment_start' => ['required', 'date_format:Y-m'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'installment_period' => ['required', 'integer', 'min:1', 'max:360'],
            'repayment_amount' => ['nullable', 'numeric', 'min:0'],
            'installment' => ['nullable', 'numeric', 'min:0'],
            'installments_cleared' => ['nullable', 'integer', 'min:0', 'lte:installment_period'],
            'is_active' => ['required', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [], ['permitted_by' => 'permitted by', 'repayment_start' => 'repayment from', 'installments_cleared' => 'installments cleared']);

        $schedule = Loan::schedule((float) $data['amount'], (float) $data['interest_rate'], (int) $data['installment_period']);

        return [
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'permitted_by_id' => Employee::where('uuid', $data['permitted_by'])->value('id'),
            'details' => $data['details'] ?? null,
            'amount' => $data['amount'],
            'interest_rate' => $data['interest_rate'],
            'installment_period' => $data['installment_period'],
            'repayment_amount' => $data['repayment_amount'] ?? $schedule['repayment'],
            'installment' => $data['installment'] ?? $schedule['installment'],
            'installments_cleared' => $data['installments_cleared'] ?? $loan?->installments_cleared ?? 0,
            'approved_date' => $data['approved_date'],
            'repayment_start' => $data['repayment_start'].'-01',
            'is_active' => $data['is_active'],
        ];
    }

    private function storeAttachment(Request $request, Loan $loan): void
    {
        if (! $request->hasFile('attachment')) {
            return;
        }

        $disk = Storage::disk(config('filesystems.employee_files'));
        $old = $loan->attachment_path;
        $file = $request->file('attachment');
        $loan->update(['attachment_path' => $disk->putFileAs("loans/{$loan->uuid}", $file, 'attachment-'.Str::random(8).'.'.$file->guessExtension())]);

        if ($old) {
            $disk->delete($old);
        }
    }
}
