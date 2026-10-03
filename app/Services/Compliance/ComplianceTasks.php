<?php

namespace App\Services\Compliance;

use App\Models\Company;
use App\Models\ComplianceFormValue;
use App\Models\ComplianceTask;
use App\Models\Employee;
use App\Models\MedicalExamination;
use App\Models\Payslip;
use App\Models\SalarySheet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps each company's To Do List up to date: creates the tasks that fall
 * due (config('compliance.tasks')) and completes the ones the HRMS can see
 * are done - an ID card assigned, salary generated, a signed PF / PT return
 * or police verification uploaded, a medical examination recorded.
 */
class ComplianceTasks
{
    /** Days after an employee is added that their joining tasks fall due. */
    private const EMPLOYEE_DAYS = 15;

    /** Months back that monthly tasks are created for. */
    private const MONTHS = 3;

    /**
     * Sync, at most every few minutes per company (each sync is several
     * queries against a distant database), unless forced.
     */
    public function refresh(Company $company, bool $force = false): void
    {
        if ($force || Cache::add("compliance-tasks-synced:{$company->id}", true, now()->addMinutes(5))) {
            $this->sync($company);
        }
    }

    public function sync(Company $company): void
    {
        $this->create($company);
        $this->complete($company);
    }

    private function create(Company $company): void
    {
        $definitions = config('compliance.tasks');
        $existing = ComplianceTask::withTrashed()->where('company_id', $company->id)->where('type', '!=', 'custom')
            ->get(['type', 'employee_id', 'period'])
            ->map(fn (ComplianceTask $t) => $t->type.'|'.$t->employee_id.'|'.$t->period)->flip();
        $rows = [];
        $add = function (string $type, ?int $employeeId, string $period, CarbonImmutable $due, string $suffix = '') use ($company, $definitions, $existing, &$rows) {
            if (! isset($existing[$type.'|'.$employeeId.'|'.$period])) {
                $d = $definitions[$type];
                $rows[] = (new ComplianceTask([
                    'company_id' => $company->id, 'type' => $type, 'employee_id' => $employeeId, 'period' => $period,
                    'title' => $d['title'].$suffix, 'category' => $d['category'], 'department' => $d['department'],
                    'due_on' => $due->toDateString(), 'priority' => 'high',
                ]));
            }
        };

        // Once per current employee.
        $employees = Employee::where('company_id', $company->id)->current()->get(['id', 'date_of_joining', 'created_at', 'works_on_hazardous_process']);
        foreach ($employees as $employee) {
            $added = CarbonImmutable::parse($employee->created_at)->startOfDay();
            $start = $employee->date_of_joining ? CarbonImmutable::parse($employee->date_of_joining)->max($added) : $added;
            foreach ($definitions as $type => $d) {
                if ($d['every'] === 'employee') {
                    $add($type, $employee->id, '', $start->addDays(self::EMPLOYEE_DAYS));
                }
            }
        }

        // Monthly, for the last few months (not before the company was added).
        $first = CarbonImmutable::today()->startOfMonth()->subMonthsNoOverflow(self::MONTHS)
            ->max(CarbonImmutable::parse($company->created_at)->startOfMonth()->subMonthNoOverflow());
        $last = CarbonImmutable::today()->startOfMonth()->subMonthNoOverflow();
        $deducted = Payslip::where('company_id', $company->id)->whereDate('month', '>=', $first)->whereDate('month', '<=', $last)
            ->get(['month', 'deductions'])->groupBy(fn (Payslip $p) => $p->month->format('Y-m'))
            ->map(fn ($slips) => $slips->flatMap(fn (Payslip $p) => collect($p->deductions)->where('amount', '>', 0)->pluck('key'))->unique()->all());

        for ($month = $first; $month->lte($last); $month = $month->addMonthNoOverflow()) {
            foreach ($definitions as $type => $d) {
                if ($d['every'] === 'monthly' && (! isset($d['deduction']) || in_array($d['deduction'], $deducted[$month->format('Y-m')] ?? [], true))) {
                    $add($type, null, $month->format('Y-m'), $month->addMonthNoOverflow()->setDay($d['due_day']), ' For the Month '.$month->format('F Y'));
                }
            }
        }

        // Half-yearly, when anyone works on a hazardous process: this half and the last.
        if ($employees->contains('works_on_hazardous_process', true)) {
            $half = CarbonImmutable::today()->month <= 6 ? CarbonImmutable::today()->startOfYear() : CarbonImmutable::today()->startOfYear()->addMonths(6);
            foreach ([$half->subMonths(6), $half] as $start) {
                $period = $start->year.'-H'.($start->month === 1 ? 1 : 2);
                $add('health-register', null, $period, $start->addMonths(5)->endOfMonth()->startOfDay(), ' ('.$start->format('M').' - '.$start->addMonths(5)->format('M Y').')');
            }
        }

        foreach ($rows as $task) {
            $task->save();
        }
    }

    /**
     * Complete pending tasks the records show are done.
     */
    private function complete(Company $company): void
    {
        $pending = ComplianceTask::where('company_id', $company->id)->whereNull('completed_at')->where('type', '!=', 'custom')->get();
        if ($pending->isEmpty()) {
            return;
        }

        $employeeIds = $pending->pluck('employee_id')->filter()->unique();
        $examined = MedicalExamination::where('company_id', $company->id)->get(['employee_id', 'examined_on']);
        $idCards = Employee::whereIn('id', $employeeIds)->whereNotNull('id_card_assigned_on')->pluck('id')->flip();
        $signed = ComplianceFormValue::where('company_id', $company->id)->whereIn('form', ['police-verification', 'pf-statement', 'pt-form-5'])
            ->whereNotNull('signed_copy_path')->get(['form', 'period'])->map(fn ($v) => $v->form.'|'.$v->period)->flip();
        $sheets = SalarySheet::where('company_id', $company->id)->get(['month'])->map(fn ($s) => $s->month->format('Y-m'))->flip();

        foreach ($pending as $task) {
            $done = match ($task->type) {
                'form-33' => $examined->contains('employee_id', $task->employee_id),
                'id-card' => isset($idCards[$task->employee_id]),
                'police-verification' => isset($signed['police-verification|employee:'.$task->employee_id]),
                'salary' => isset($sheets[$task->period]),
                'pf-return' => isset($signed['pf-statement|'.$task->period]),
                'pt-return' => isset($signed['pt-form-5|'.$task->period]),
                'health-register' => $this->examinedInHalf($examined, $task->period),
                default => false,
            };

            if ($done) {
                $task->update(['completed_at' => now(), 'completed_by' => null]);
            }
        }
    }

    private function examinedInHalf($examined, string $period): bool
    {
        [$year, $half] = explode('-H', $period);
        $from = CarbonImmutable::create((int) $year, $half === '1' ? 1 : 7, 1);

        return $examined->contains(fn ($x) => $x->examined_on->between($from, $from->addMonths(5)->endOfMonth()));
    }

    /**
     * Where to go to do a task, if anywhere.
     */
    public static function link(ComplianceTask $task): ?string
    {
        $company = $task->company;
        $employee = $task->employee?->uuid;

        return match ($task->type) {
            'form-33' => route('compliance.forms.show', [$company, 'form-27a-fitness'] + array_filter(['employee' => $employee])),
            'id-card' => route('employees.id-cards'),
            'police-verification' => route('compliance.forms.show', [$company, 'police-verification'] + array_filter(['employee' => $employee])),
            'salary' => route('payroll.index'),
            'pf-return' => route('compliance.forms.show', [$company, 'pf-act', 'month' => $task->period]),
            'pt-return' => route('compliance.forms.show', [$company, 'form-5-pt', 'month' => $task->period]),
            'esic' => route('payroll.salaries'),
            'health-register' => route('compliance.forms.show', [$company, 'form-32-health-register']),
            default => null,
        };
    }
}
