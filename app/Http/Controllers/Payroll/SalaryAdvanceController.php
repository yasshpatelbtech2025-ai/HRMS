<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryAdvance;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll › Salary Advance: money paid ahead of salary and the month it is
 * recovered from.
 */
class SalaryAdvanceController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        return view('payroll.advances', [
            'advances' => $this->filtered($request)->paginate($perPage = $this->perPage($request))->withQueryString(),
            'perPage' => $perPage,
            'employees' => Employee::current()->orderBy('company_id')->orderBy('employee_code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $advance = SalaryAdvance::create($data);
        $this->storeAttachment($request, $advance);

        return back()->with('status', __('Salary advance added.'));
    }

    public function update(Request $request, SalaryAdvance $advance): RedirectResponse
    {
        $advance->update($this->validated($request, $advance));
        $this->storeAttachment($request, $advance);

        return back()->with('status', __('Salary advance updated.'));
    }

    public function destroy(SalaryAdvance $advance): RedirectResponse
    {
        if ($advance->attachment_path) {
            Storage::disk(config('filesystems.employee_files'))->delete($advance->attachment_path);
        }
        $advance->delete();

        return back()->with('status', __('Salary advance deleted.'));
    }

    public function attachment(SalaryAdvance $advance): StreamedResponse
    {
        $disk = Storage::disk(config('filesystems.employee_files'));
        abort_unless($advance->attachment_path && $disk->exists($advance->attachment_path), 404);

        return $disk->response($advance->attachment_path);
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        return TableExport::download($format, 'salary-advances',
            ['Sl', 'Employee name', 'Amount', 'Release amount', 'Salary month', 'Advance date', 'Status'],
            $this->filtered($request)->get()->map(fn (SalaryAdvance $a, int $i) => [
                $i + 1, $a->employee->full_name, $a->amount, $a->released_amount, $a->salary_month->format('Y-m'), $a->advance_date->toDateString(), $a->is_active ? 'Active' : 'Inactive',
            ]),
        );
    }

    private function filtered(Request $request): Builder
    {
        return SalaryAdvance::with('employee')
            ->tap(fn (Builder $q) => $this->search($q, $request, ['employee.first_name', 'employee.last_name', 'employee.employee_code']))
            ->latest('advance_date')->latest('id');
    }

    private function validated(Request $request, ?SalaryAdvance $advance = null): array
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', Rule::exists('employees', 'uuid')->whereNull('deleted_at')],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'salary_month' => ['required', 'date_format:Y-m'],
            'advance_date' => ['required', 'date'],
            'is_active' => ['required', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [], ['salary_month' => 'salary month', 'advance_date' => 'advance date']);

        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();

        return [
            'company_id' => $employee->company_id, 'employee_id' => $employee->id, 'amount' => $data['amount'],
            'salary_month' => $data['salary_month'].'-01', 'advance_date' => $data['advance_date'], 'is_active' => $data['is_active'],
        ];
    }

    private function storeAttachment(Request $request, SalaryAdvance $advance): void
    {
        if (! $request->hasFile('attachment')) {
            return;
        }

        $disk = Storage::disk(config('filesystems.employee_files'));
        $old = $advance->attachment_path;
        $file = $request->file('attachment');
        $advance->update(['attachment_path' => $disk->putFileAs("salary-advances/{$advance->uuid}", $file, 'attachment-'.Str::random(8).'.'.$file->guessExtension())]);

        if ($old) {
            $disk->delete($old);
        }
    }
}
