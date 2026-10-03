<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\MonthlyAttendance;
use App\Support\TableExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reports: the monthly attendance sheet (in/out times by day), and tabular
 * reports — attendance summary, lateness, attendance log, daily present and
 * employee report — each viewable, printable and exportable (CSV, Excel, PDF).
 */
class ReportController extends Controller
{
    public function __construct(private MonthlyAttendance $attendance) {}

    /**
     * Reports › Attendance sheet: each employee's in and out time for every
     * day of the month.
     */
    public function sheet(Request $request): View|Response
    {
        [$companies, $company] = $this->company($request);
        $month = $this->month($request);
        $employees = $company ? $this->employedIn($company, $month->startOfMonth(), $month->endOfMonth()) : collect();
        $grid = $this->attendance->forMonth($employees, $month);

        if ($format = $request->query('format')) {
            $headings = ['ID', 'Employee Name', 'Time', ...array_map(fn ($d) => sprintf('%02d', $d), range(1, $month->daysInMonth))];
            $rows = $employees->flatMap(fn (Employee $e) => [
                [$e->employee_code, $e->full_name, 'In Time', ...array_map(fn ($d) => $d['in']?->format('H:i') ?? ($d['status'] === 'A' ? '' : $d['status']), array_values($grid[$e->id]['days']))],
                ['', '', 'Out Time', ...array_map(fn ($d) => $d['out']?->format('H:i') ?? '', array_values($grid[$e->id]['days']))],
            ]);

            return TableExport::download($format, 'attendance-sheet-'.$month->format('Y-m'), $headings, $rows, 'Monthly attendance sheet — '.$company?->establishment_name.' — '.$month->format('F Y'));
        }

        return view('reports.sheet', compact('companies', 'company', 'month', 'employees', 'grid'));
    }

    /**
     * The tabular reports. Each builds its filters, headings and rows.
     */
    public function show(Request $request, string $report): View|Response
    {
        [$companies, $company] = $this->company($request);

        $built = match ($report) {
            'attendance' => $this->attendanceSummary($request, $company),
            'lateness' => $this->lateness($request, $company),
            'attendance-log' => $this->log($request, $company),
            'daily-present' => $this->dailyPresent($request, $company),
            'employees' => $this->employeeReport($request, $company),
            default => abort(404),
        };

        if ($format = $request->query('format')) {
            return TableExport::download($format, $report.'-'.now()->format('Ymd'), $built['headings'], $built['rows'], $built['title'].' — '.$company?->establishment_name.' — '.$built['period']);
        }

        return view('reports.table', ['companies' => $companies, 'company' => $company, 'report' => $report] + $built);
    }

    /**
     * Reports › Attendance report: per employee, the day counts, hours and
     * overtime over a date range.
     */
    private function attendanceSummary(Request $request, ?Company $company): array
    {
        [$from, $to] = $this->range($request);
        $employees = $company ? $this->employedIn($company, $from, $to) : collect();
        $data = $this->attendance->forRange($employees, $from, $to);

        return [
            'title' => __('Attendance report'),
            'filters' => ['range'],
            'period' => $from->toDateString().' – '.$to->toDateString(),
            'headings' => ['Employee id', 'Employee name', 'Department', 'Present', 'Half day', 'LOP', 'Week off', 'Holiday', 'Leave', 'COFF', 'Worked hours', 'OT hours'],
            'rows' => $employees->map(function (Employee $e) use ($data) {
                $t = $data[$e->id]['totals'];

                return [$e->employee_code, $e->full_name, $e->subDepartment?->name, $t['P'], $t['HD'], $t['A'], $t['WO'], $t['PH'], $t['LEAVE'], $t['COFF'], round($t['hours'], 2), round($t['ot'], 2)];
            })->all(),
        ];
    }

    /**
     * Reports › Lateness closing attendance report: arrivals after the shift
     * start (beyond the company's punch buffer) and departures before the end.
     */
    private function lateness(Request $request, ?Company $company): array
    {
        $month = $this->month($request);
        $buffer = $company ? (AttendanceSetting::where('company_id', $company->id)->value('punch_buffer_minutes') ?? 0) : 0;
        $employees = $company ? $this->employedIn($company, $month->startOfMonth(), $month->endOfMonth()) : collect();
        $data = $this->attendance->forMonth($employees, $month);
        $rows = [];

        foreach ($employees as $employee) {
            foreach ($data[$employee->id]['days'] as $date => $day) {
                $timing = $employee->shift?->timings[$day['code'] ?? ''] ?? null;
                if (! $timing || ! $day['in']) {
                    continue;
                }

                $start = CarbonImmutable::parse("{$date} {$timing['start']}");
                $end = CarbonImmutable::parse("{$date} {$timing['end']}");
                if ($end->lte($start)) {
                    $end = $end->addDay();
                }
                $late = $day['in']->gt($start->addMinutes($buffer)) ? (int) $start->diffInMinutes($day['in']) : 0;
                $early = $day['out'] && $day['out']->lt($end) ? (int) $day['out']->diffInMinutes($end) : 0;

                if ($late || $early) {
                    $rows[] = [$employee->employee_code, $employee->full_name, $date, $day['code'], $timing['start'].'–'.$timing['end'],
                        $day['in']->format('H:i'), $day['out']?->format('H:i'), $late, $early, $day['status']];
                }
            }
        }

        return [
            'title' => __('Lateness closing attendance report'),
            'filters' => ['month'],
            'period' => $month->format('F Y'),
            'note' => __('Late = in after the shift start plus the :minutes-minute punch buffer (Configs › Attendance). Early = out before the shift end.', ['minutes' => $buffer]),
            'headings' => ['Employee id', 'Employee name', 'Date', 'Shift', 'Shift time', 'In time', 'Out time', 'Late by (min)', 'Left early by (min)', 'Status'],
            'rows' => $rows,
        ];
    }

    /**
     * Reports › Attendance log: every punch in the date range, in order.
     */
    private function log(Request $request, ?Company $company): array
    {
        [$from, $to] = $this->range($request);

        $punches = $company ? Attendance::where('company_id', $company->id)
            ->whereDate('work_date', '>=', $from)->whereDate('work_date', '<=', $to)
            ->whereHas('employee')->with(['employee', 'shift'])->orderBy('punched_at')->get() : collect();

        return [
            'title' => __('Attendance log'),
            'filters' => ['range'],
            'period' => $from->toDateString().' – '.$to->toDateString(),
            'headings' => ['Employee id', 'Employee name', 'Date', 'Time', 'Type', 'Source', 'Device', 'Shift', 'Actual shift', 'Missing', 'Locked'],
            'rows' => $punches->map(fn (Attendance $p) => [
                $p->employee->employee_code, $p->employee->full_name, $p->work_date->toDateString(), $p->punched_at->format('Y-m-d H:i'),
                $p->isIn() ? 'In' : 'Out', strtoupper($p->source), $p->device, $p->shift?->name, $p->shift_code,
                $p->is_missing ? 'Yes' : '', $p->is_locked ? 'Yes' : '',
            ])->all(),
        ];
    }

    /**
     * Reports › Daily present: who was present on a day, with times.
     */
    private function dailyPresent(Request $request, ?Company $company): array
    {
        $date = $this->date($request);
        $employees = $company ? $this->employedIn($company, $date, $date) : collect();
        $data = $this->attendance->forRange($employees, $date, $date);

        $present = $employees->filter(fn (Employee $e) => in_array($data[$e->id]['days'][$date->toDateString()]['status'], ['P', 'HD'], true));

        return [
            'title' => __('Daily present'),
            'filters' => ['date'],
            'period' => $date->format('d M Y'),
            'note' => __(':present of :total employees present.', ['present' => $present->count(), 'total' => $employees->count()]),
            'headings' => ['Employee id', 'Employee name', 'Department', 'Designation', 'Shift', 'In time', 'Out time', 'Worked hours', 'Status'],
            'rows' => $present->map(function (Employee $e) use ($data, $date) {
                $d = $data[$e->id]['days'][$date->toDateString()];

                return [$e->employee_code, $e->full_name, $e->subDepartment?->name, $e->position?->name, $d['code'] ?? $e->shift?->name,
                    $d['in']?->format('H:i'), $d['out']?->format('H:i'), $d['hours'], $d['status'] === 'HD' ? 'Half day' : 'Present'];
            })->values()->all(),
        ];
    }

    /**
     * Reports › Employee report: the company's employees with their main details.
     */
    private function employeeReport(Request $request, ?Company $company): array
    {
        $status = in_array($request->query('status'), ['current', 'separated', 'all'], true) ? $request->query('status') : 'current';

        $employees = $company ? Employee::where('company_id', $company->id)
            ->when($status === 'current', fn ($q) => $q->current())
            ->when($status === 'separated', fn ($q) => $q->whereNotNull('separation_date')->whereDate('separation_date', '<=', today()))
            ->with(['subDepartment', 'position', 'shift'])->orderBy('employee_code')->get() : collect();

        return [
            'title' => __('Employee report'),
            'filters' => ['status'],
            'status' => $status,
            'period' => ucfirst($status),
            'headings' => ['Employee id', 'Employee name', 'Department', 'Designation', 'Gender', 'Date of birth', 'Joining date', 'Mobile no', 'Shift', 'Employee type', 'Status', 'Separation date'],
            'rows' => $employees->map(fn (Employee $e) => [
                $e->employee_code, $e->full_name, $e->subDepartment?->name, $e->position?->name, $e->gender?->label(),
                $e->date_of_birth?->toDateString(), $e->date_of_joining?->toDateString(), $e->phone, $e->shift?->name,
                $e->employment_type?->label(), $e->status->label(), $e->separation_date?->toDateString(),
            ])->all(),
        ];
    }

    /**
     * @return array{Collection<int, Company>, ?Company}
     */
    private function company(Request $request): array
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();

        return [$companies, $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first()];
    }

    private function employedIn(Company $company, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Employee::where('company_id', $company->id)
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereDate('date_of_joining', '<=', $to))
            ->where(fn ($q) => $q->whereNull('separation_date')->orWhereDate('separation_date', '>', $from))
            ->with(['shift', 'subDepartment', 'position'])->orderBy('employee_code')->get();
    }

    private function month(Request $request): CarbonImmutable
    {
        $month = $request->query('month');

        return is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) ? CarbonImmutable::createFromFormat('!Y-m', $month) : CarbonImmutable::today()->startOfMonth();
    }

    private function date(Request $request): CarbonImmutable
    {
        $date = $request->query('date');

        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? CarbonImmutable::parse($date) : CarbonImmutable::today();
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable} at most 92 days
     */
    private function range(Request $request): array
    {
        $from = is_string($request->query('from')) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->query('from')) ? CarbonImmutable::parse($request->query('from')) : CarbonImmutable::today()->startOfMonth();
        $to = is_string($request->query('to')) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->query('to')) ? CarbonImmutable::parse($request->query('to')) : CarbonImmutable::today();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to->min($from->addDays(91))];
    }
}
