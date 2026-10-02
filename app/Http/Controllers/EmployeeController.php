<?php

namespace App\Http\Controllers;

use App\Actions\SaveEmployee;
use App\Enums\EmployeeStatus;
use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Requests\EmployeeRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAddress;
use App\Models\Position;
use App\Models\Shift;
use App\Models\SubDepartment;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Employee › Employee tab: the employee list with its filters, the Add /
 * Edit employee form, the details sheet, status, separation and letters.
 */
class EmployeeController extends Controller
{
    use ListsRecords;

    /** Filter panel fields => how each constrains the list. */
    private const FILTERS = [
        'company_id', 'name', 'employee_code', 'employment_type', 'department_id', 'sub_department_id',
        'position_id', 'blood_group', 'country', 'gender', 'marital_status',
    ];

    public function index(Request $request): View
    {
        $perPage = $this->perPage($request, allowAll: true);

        return view('employees.index', [
            'employees' => $this->filtered($request, Employee::current())->paginate($perPage)->withQueryString(),
            'perPage' => $perPage,
            'columns' => $this->visibleColumns($request),
            'filters' => [
                'companies' => Company::with('parent')->orderBy('establishment_name')->get(),
                'departments' => Department::orderBy('name')->get(),
                'subDepartments' => SubDepartment::orderBy('name')->get(),
                'positions' => Position::orderBy('name')->get(),
            ],
            'importCompanies' => Company::with('parent')->orderBy('establishment_name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('employees.form', $this->formData(new Employee([
            'country' => 'India', 'pay_frequency' => 'monthly', 'tax_regime' => 'new', 'daily_hour' => 8,
        ])));
    }

    public function store(EmployeeRequest $request, SaveEmployee $save): RedirectResponse
    {
        $employee = $save(new Employee, $request->validated(), $this->uploads($request));

        return redirect()->route('employees.index')->with('status', __('Employee :name added with employee id :code.', [
            'name' => $employee->full_name, 'code' => $employee->employee_code,
        ]));
    }

    public function show(Employee $employee): View
    {
        $employee->load(['company', 'department', 'subDepartment', 'position', 'shift', 'reportingOfficer', 'addresses', 'qualifications', 'experiences', 'familyMembers', 'salaryElements']);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $employee->load(['addresses', 'qualifications', 'experiences', 'familyMembers', 'salaryElements', 'shift']);

        return view('employees.form', $this->formData($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee, SaveEmployee $save): RedirectResponse
    {
        $save($employee, $request->validated(), $this->uploads($request));

        return redirect()->route('employees.index')->with('status', __('Employee :name updated.', ['name' => $employee->full_name]));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if (Employee::where('reporting_officer_id', $employee->id)->exists()) {
            return back()->with('error', __(':name is the reporting officer of other employees. Change their reporting officer first.', ['name' => $employee->full_name]));
        }

        $employee->delete();

        return back()->with('status', __('Employee :name deleted.', ['name' => $employee->full_name]));
    }

    public function toggleStatus(Employee $employee): RedirectResponse
    {
        $employee->update(['status' => $employee->status === EmployeeStatus::Active ? EmployeeStatus::Inactive : EmployeeStatus::Active]);

        return back()->with('status', __(':name is now :status.', ['name' => $employee->full_name, 'status' => strtolower($employee->status->label())]));
    }

    /**
     * Set or clear the employee's separation date. Employees whose date has
     * passed move to the Separation employees list.
     */
    public function separate(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate(['separation_date' => ['nullable', 'date', 'after_or_equal:'.$employee->date_of_joining->toDateString()]]);

        $employee->update(['separation_date' => $data['separation_date'] ?? null]);

        return back()->with('status', $employee->separation_date
            ? __('Separation date for :name set to :date.', ['name' => $employee->full_name, 'date' => $employee->separation_date->format('d M Y')])
            : __('Separation date for :name cleared.', ['name' => $employee->full_name]));
    }

    /**
     * Show or hide an optional list column for the signed-in user.
     */
    public function toggleColumn(Request $request): RedirectResponse
    {
        $column = $request->validate(['column' => ['required', Rule::in(array_keys(config('employee.optional_columns')))]])['column'];
        $user = $request->user();
        $visible = $this->visibleColumns($request);

        $user->forceFill(['preferences' => array_merge($user->preferences ?? [], [
            'employee_columns' => array_merge($visible, [$column => ! $visible[$column]]),
        ])])->save();

        return back();
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        $employees = $this->filtered($request, Employee::current())->get();

        return TableExport::download($format, 'employees',
            ['Sl', 'Company name', 'Employee id', 'Name of employee', 'Mobile no', 'Department', 'Status'],
            $employees->map(fn (Employee $e, int $i) => [
                $i + 1, $e->company?->establishment_name, $e->employee_code, $e->full_name, $e->phone,
                $e->subDepartment?->name ?? $e->department?->name, $e->status->label(),
            ]),
        );
    }

    /**
     * Stream one of the employee's stored files (photo or a document scan).
     */
    public function file(Employee $employee, string $file): StreamedResponse
    {
        $path = $employee->{SaveEmployee::FILES[$file] ?? abort(404)} ?? abort(404);
        $disk = Storage::disk(config('filesystems.employee_files'));

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, basename($path), ['Cache-Control' => 'private, max-age=3600']);
    }

    /**
     * Call, offer and joining letters. The old system's letter templates
     * haven't been provided yet.
     */
    public function letter(Employee $employee, string $letter): View
    {
        abort_unless(in_array($letter, ['call', 'offer', 'joining'], true), 404);

        return view('employees.letter', ['employee' => $employee, 'letter' => $letter]);
    }

    /**
     * The list query with the filter panel, search and sort applied.
     */
    public function filtered(Request $request, Builder $query): Builder
    {
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query->with(['company', 'department', 'subDepartment', 'position', 'shift'])
            ->tap(fn (Builder $q) => $this->search($q, $request, ['first_name', 'middle_name', 'last_name', 'employee_code', 'phone', 'email']));

        foreach (self::FILTERS as $filter) {
            $value = $request->query($filter);

            if (blank($value) || ! is_scalar($value)) {
                continue;
            }

            match ($filter) {
                'name' => $query->where(function (Builder $q) use ($value) {
                    foreach (preg_split('/\s+/', mb_strtolower(trim($value))) as $word) {
                        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $word).'%';
                        $q->where(fn (Builder $w) => $w->whereRaw('lower(first_name) like ?', [$like])
                            ->orWhereRaw('lower(middle_name) like ?', [$like])
                            ->orWhereRaw('lower(last_name) like ?', [$like]));
                    }
                }),
                default => $query->where($filter, $value),
            };
        }

        return match ($request->query('sort')) {
            'employee_code' => $query->orderBy('employee_code', $direction),
            'status' => $query->orderBy('status', $direction)->orderBy('employee_code'),
            'joining_date' => $query->orderBy('date_of_joining', $direction)->orderBy('employee_code'),
            'shift' => $query->orderBy(Shift::select('name')->whereColumn('shifts.id', 'employees.shift_id'), $direction)->orderBy('employee_code'),
            default => $query->orderBy(Company::select('establishment_name')->whereColumn('companies.id', 'employees.company_id'), $direction)->orderBy('employee_code'),
        };
    }

    /**
     * @return array<string, bool> optional column => shown
     */
    private function visibleColumns(Request $request): array
    {
        $saved = $request->user()->preferences['employee_columns'] ?? [];

        return collect(config('employee.optional_columns'))
            ->map(fn (array $column, string $key) => (bool) ($saved[$key] ?? $column[1]))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function uploads(EmployeeRequest $request): array
    {
        $files = collect(SaveEmployee::FILES)->keys()
            ->filter(fn ($field) => $request->hasFile($field))
            ->mapWithKeys(fn ($field) => [$field => $request->file($field)])
            ->all();

        // A photo taken with the camera (or resized in the browser) wins over a raw upload.
        if ($request->filled('photo_data')) {
            $files['photo'] = $request->input('photo_data');
        }

        return $files;
    }

    /**
     * Everything the Add / Edit form needs: the employee plus each company's
     * departments, sub departments, designations, shifts and possible
     * reporting officers, and suggestions for the address fields.
     *
     * @return array<string, mixed>
     */
    private function formData(Employee $employee): array
    {
        $places = fn (string $column) => EmployeeAddress::whereNotNull($column)->distinct()->pluck($column)
            ->merge(Company::whereNotNull($column)->distinct()->pluck($column))
            ->map(fn ($value) => trim($value))->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        return [
            'employee' => $employee,
            'companies' => Company::with('parent')->withCount('employees')->orderBy('establishment_name')->get(),
            'departments' => Department::orderBy('name')->get(['id', 'company_id', 'name']),
            'subDepartments' => SubDepartment::where(fn ($q) => $q->where('is_active', true)->orWhere('id', $employee->sub_department_id))
                ->orderBy('name')->get(['id', 'department_id', 'name']),
            'positions' => Position::where(fn ($q) => $q->where('is_active', true)->orWhere('id', $employee->position_id))
                ->orderBy('name')->get(['id', 'company_id', 'name']),
            'shifts' => Shift::orderBy('name')->get(['company_id', 'name']),
            'officers' => Employee::current()->when($employee->exists, fn ($q) => $q->whereKeyNot($employee->id))
                ->orderBy('employee_code')->get(['id', 'company_id', 'employee_code', 'first_name', 'middle_name', 'last_name']),
            'states' => collect(CompanyController::STATES)->merge($places('state'))->unique(fn ($v) => mb_strtolower($v))->sort(SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'districts' => $places('district'),
            'talukas' => $places('taluka'),
        ];
    }
}
