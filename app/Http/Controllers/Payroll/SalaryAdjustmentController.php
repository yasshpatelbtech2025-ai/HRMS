<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryAdjustment;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll › Salary adjustments: one-off earnings and deductions for an
 * employee's month (arrears, canteen, penalties…).
 */
class SalaryAdjustmentController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        return view('payroll.adjustments', [
            'adjustments' => $this->filtered($request)->paginate($perPage = $this->perPage($request))->withQueryString(),
            'perPage' => $perPage,
            'employees' => Employee::current()->orderBy('company_id')->orderBy('employee_code')->get(),
            'titles' => collect(SalaryAdjustment::TITLES)->merge(SalaryAdjustment::distinct()->pluck('title'))->unique(fn ($t) => mb_strtolower($t))->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        SalaryAdjustment::create($this->validated($request));

        return back()->with('status', __('Salary adjustment added.'));
    }

    public function update(Request $request, SalaryAdjustment $adjustment): RedirectResponse
    {
        $adjustment->update($this->validated($request));

        return back()->with('status', __('Salary adjustment updated.'));
    }

    public function destroy(SalaryAdjustment $adjustment): RedirectResponse
    {
        $adjustment->delete();

        return back()->with('status', __('Salary adjustment deleted.'));
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        return TableExport::download($format, 'salary-adjustments',
            ['Sl', 'Employee name', 'Title', 'Amount', 'Salary month', 'Adjustment type'],
            $this->filtered($request)->get()->map(fn (SalaryAdjustment $a, int $i) => [
                $i + 1, $a->employee->full_name, $a->title, $a->amount, $a->salary_month->format('Y-m'), SalaryAdjustment::TYPES[$a->type],
            ]),
        );
    }

    private function filtered(Request $request): Builder
    {
        return SalaryAdjustment::with('employee')
            ->tap(fn (Builder $q) => $this->search($q, $request, ['title', 'employee.first_name', 'employee.last_name', 'employee.employee_code']))
            ->latest('salary_month')->latest('id');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', Rule::exists('employees', 'uuid')->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'salary_month' => ['required', 'date_format:Y-m'],
            'type' => ['required', Rule::in(array_keys(SalaryAdjustment::TYPES))],
        ], [], ['salary_month' => 'salary month', 'type' => 'adjustment type']);

        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();

        return [
            'company_id' => $employee->company_id, 'employee_id' => $employee->id, 'title' => trim($data['title']),
            'amount' => $data['amount'], 'salary_month' => $data['salary_month'].'-01', 'type' => $data['type'],
        ];
    }
}
