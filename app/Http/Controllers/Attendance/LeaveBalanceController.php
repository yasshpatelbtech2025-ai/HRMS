<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\Attendance\LeaveBalances;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attendance › Leave › Leave balance: each employee's total, used and
 * remaining days of every leave type for a company and year.
 */
class LeaveBalanceController extends Controller
{
    public function __construct(private LeaveBalances $balances) {}

    public function index(Request $request): View
    {
        [$companies, $company, $year] = $this->filters($request);
        $employees = $company ? $this->employees($company, $year) : collect();
        $types = $company ? $company->leaveTypes()->orderBy('name')->get() : collect();

        return view('attendance.leave.balance', [
            'companies' => $companies,
            'company' => $company,
            'year' => $year,
            'employees' => $employees,
            'types' => $types,
            'balances' => $this->balances->forYear($employees->pluck('id'), $types, $year),
        ]);
    }

    public function update(Request $request, Employee $employee, LeaveType $leaveType): RedirectResponse
    {
        abort_unless($leaveType->company_id === $employee->company_id, 404);

        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'total' => ['required', 'numeric', 'min:0', 'max:366'],
            'used' => ['required', 'numeric', 'min:0', 'max:366'],
        ]);

        $approved = $this->balances->forYear(collect([$employee->id]), collect([$leaveType]), $data['year'])[$employee->id][$leaveType->id]['approved'];

        LeaveBalance::updateOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => $data['year']],
            ['total' => $data['total'], 'used_adjustment' => $data['used'] - $approved],
        );

        return back()->with('status', __('Leave balance updated for :name.', ['name' => $employee->full_name]));
    }

    public function export(Request $request): StreamedResponse
    {
        [, $company, $year] = $this->filters($request);
        abort_unless($company, 404);

        $employees = $this->employees($company, $year);
        $types = $company->leaveTypes()->orderBy('name')->get();
        $balances = $this->balances->forYear($employees->pluck('id'), $types, $year);

        $headings = ['SL', 'Card No', 'Employee Name'];
        foreach ($types as $type) {
            array_push($headings, "{$type->code} Total", "{$type->code} Used", "{$type->code} Balance");
        }

        return TableExport::download('xlsx', "leave-balance-{$year}", $headings, $employees->values()->map(function (Employee $e, int $i) use ($types, $balances) {
            $row = [$i + 1, $e->employee_code, $e->full_name];
            foreach ($types as $type) {
                $b = $balances[$e->id][$type->id];
                array_push($row, $b['total'], $b['used'], $b['balance']);
            }

            return $row;
        }));
    }

    private function filters(Request $request): array
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();
        $year = $request->integer('year', (int) date('Y'));

        return [$companies, $company, $year >= 2000 && $year <= 2100 ? $year : (int) date('Y')];
    }

    private function employees(Company $company, int $year): Collection
    {
        return Employee::where('company_id', $company->id)
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereYear('date_of_joining', '<=', $year))
            ->where(fn ($q) => $q->whereNull('separation_date')->orWhereYear('separation_date', '>=', $year))
            ->orderBy('employee_code')->get();
    }
}
