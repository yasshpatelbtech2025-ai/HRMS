<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Requests\SubDepartmentRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Employee › Department tab: sub departments, each filed under a department
 * of a company. Departments are created on the fly when a new name is typed.
 */
class SubDepartmentController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        return view('employees.departments.index', [
            'subDepartments' => $this->filtered($request)->paginate($perPage = $this->perPage($request))->withQueryString(),
            'perPage' => $perPage,
            'companies' => Company::with('parent')->orderBy('establishment_name')->get(),
            'departments' => Department::orderBy('name')->get(['name', 'company_id']),
        ]);
    }

    public function store(SubDepartmentRequest $request): RedirectResponse
    {
        SubDepartment::create([
            'company_id' => $request->integer('company_id'),
            'department_id' => $request->department()->id,
            'name' => trim($request->input('name')),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('Sub department added.'));
    }

    public function update(SubDepartmentRequest $request, SubDepartment $subDepartment): RedirectResponse
    {
        $subDepartment->update([
            'company_id' => $request->integer('company_id'),
            'department_id' => $request->department()->id,
            'name' => trim($request->input('name')),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('Sub department updated.'));
    }

    public function destroy(SubDepartment $subDepartment): RedirectResponse
    {
        $subDepartment->delete();

        return $this->deleted($subDepartment, __('Sub department deleted.'));
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        return TableExport::download($format, 'sub-departments',
            ['Sl', 'Company name', 'Sub department name', 'Department name'],
            $this->filtered($request)->get()->map(fn (SubDepartment $row, int $i) => [
                $i + 1, $row->company->establishment_name, $row->name, $row->department->name,
            ]),
        );
    }

    private function filtered(Request $request): Builder
    {
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = SubDepartment::with(['company', 'department'])
            ->tap(fn (Builder $q) => $this->search($q, $request, ['name', 'department.name', 'company.establishment_name']));

        return match ($request->query('sort')) {
            'name' => $query->orderBy('name', $direction),
            'department' => $query->orderBy(Department::select('name')->whereColumn('departments.id', 'sub_departments.department_id'), $direction)->orderBy('name'),
            'status' => $query->orderBy('is_active', $direction === 'asc' ? 'desc' : 'asc')->orderBy('name'),
            default => $query->orderBy(Company::select('establishment_name')->whereColumn('companies.id', 'sub_departments.company_id'), $direction)->orderBy('name'),
        };
    }
}
