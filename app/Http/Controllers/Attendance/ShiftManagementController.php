<?php

namespace App\Http\Controllers\Attendance;

use App\Rules\AccessibleEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ShiftRoster;
use App\Models\SubDepartment;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Attendance › Shift management: per department, which shift parts rotate
 * and how often, which employees are on the roster with their weekly off,
 * and the resulting shift (or WO) for every day of the month. The rotation
 * is laid out in the browser; a ticked "Edit" lets days be changed by hand.
 */
class ShiftManagementController extends Controller
{
    /** Codes a roster day can hold. */
    public const CODES = ['GS', 'DS', 'FS', 'SS', 'NS', 'WO'];

    /** Rotating parts offered per department, as in the old system. */
    public const PARTS = ['FS' => 'First Shift / Day Shift', 'SS' => 'Second Shift', 'NS' => 'Night Shift'];

    public const ITERATIONS = [1, 2, 6];

    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();
        $month = $this->month($request);

        $departments = $company ? SubDepartment::where('company_id', $company->id)->orderBy('name')->get() : collect();
        $rosters = $company ? ShiftRoster::where('company_id', $company->id)->whereDate('month', $month)->with('entries.employee')->get()->keyBy('sub_department_id') : collect();
        $employees = $company ? Employee::where('company_id', $company->id)
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereDate('date_of_joining', '<=', $month->endOfMonth()))
            ->where(fn ($q) => $q->whereNull('separation_date')->orWhereDate('separation_date', '>=', $month))
            ->orderBy('employee_code')->get() : collect();

        $groups = $departments->map(function (SubDepartment $department) use ($rosters, $employees) {
            $roster = $rosters->get($department->id);

            return [
                'id' => $department->id,
                'name' => $department->name,
                'parts' => $roster?->parts ?? [],
                'iteration' => $roster?->iteration ?? 6,
                'entries' => $roster ? $roster->entries->filter->employee->map(fn ($entry) => [
                    'uuid' => $entry->employee->uuid,
                    'label' => "{$entry->employee->employee_code} - {$entry->employee->full_name}",
                    'weeklyOff' => $entry->weekly_off,
                    'manual' => $entry->is_manual,
                    'days' => (object) $entry->days,
                ])->values() : [],
                'available' => $employees->where('sub_department_id', $department->id)->map(fn (Employee $e) => [
                    'uuid' => $e->uuid,
                    'label' => "{$e->employee_code} - {$e->full_name}",
                    'weeklyOff' => $e->weekly_leave_day ?? 'sunday',
                ])->values(),
            ];
        });

        return view('attendance.shift-management', compact('companies', 'company', 'month', 'groups'));
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        $month = $this->month($request);
        $request->merge(['roster' => json_decode((string) $request->input('roster'), true) ?? []]);
        $days = $month->daysInMonth;

        $data = Validator::make($request->all(), [
            'roster' => ['array'],
            'roster.*.id' => ['required', 'integer', Rule::exists('sub_departments', 'id')->where('company_id', $company->id)],
            'roster.*.parts' => ['array'],
            'roster.*.parts.*' => [Rule::in(array_keys(self::PARTS))],
            'roster.*.iteration' => ['required', 'integer', Rule::in(self::ITERATIONS)],
            'roster.*.entries' => ['array'],
            'roster.*.entries.*.uuid' => ['required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')->where('company_id', $company->id)],
            'roster.*.entries.*.weeklyOff' => ['required', Rule::in(array_keys(config('employee.weekdays')))],
            'roster.*.entries.*.manual' => ['boolean'],
            'roster.*.entries.*.days' => ['array'],
            'roster.*.entries.*.days.*' => ['nullable', Rule::in(self::CODES)],
        ])->validate();

        DB::transaction(function () use ($company, $month, $data, $days) {
            $kept = [];

            foreach ($data['roster'] ?? [] as $group) {
                if (empty($group['entries'])) {
                    continue;
                }

                $roster = ShiftRoster::updateOrCreate(
                    ['sub_department_id' => $group['id'], 'month' => $month->toDateString()],
                    ['company_id' => $company->id, 'parts' => array_values($group['parts'] ?? []), 'iteration' => $group['iteration']],
                );
                $kept[] = $roster->id;
                $ids = Employee::whereIn('uuid', collect($group['entries'])->pluck('uuid'))->pluck('id', 'uuid');

                $roster->entries()->whereNotIn('employee_id', $ids->values())->delete();

                foreach ($group['entries'] as $entry) {
                    $roster->entries()->updateOrCreate(['employee_id' => $ids[$entry['uuid']]], [
                        'weekly_off' => $entry['weeklyOff'],
                        'is_manual' => (bool) ($entry['manual'] ?? false),
                        'days' => collect($entry['days'] ?? [])->filter()
                            ->filter(fn ($code, $day) => (int) $day >= 1 && (int) $day <= $days)
                            ->mapWithKeys(fn ($code, $day) => [(string) (int) $day => $code])->all(),
                    ]);
                }
            }

            ShiftRoster::where('company_id', $company->id)->whereDate('month', $month)->whereNotIn('id', $kept)->delete();
        });

        return redirect()->route('attendance.shifts', ['company' => $company->uuid, 'year' => $month->year, 'month' => $month->month])
            ->with('status', __('Shifts for :month saved.', ['month' => $month->format('F Y')]));
    }

    private function month(Request $request): CarbonImmutable
    {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('n'));

        return ($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12)
            ? CarbonImmutable::create($year, $month, 1)
            : CarbonImmutable::today()->startOfMonth();
    }
}
