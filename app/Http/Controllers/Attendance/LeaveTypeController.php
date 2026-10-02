<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Attendance › Leave › Leave type: each company's kinds of leave, their
 * yearly allowance and whether they are paid.
 */
class LeaveTypeController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        $leaveTypes = LeaveType::with('company')
            ->tap(fn (Builder $q) => $this->search($q, $request, ['name', 'code', 'company.establishment_name']))
            ->orderBy(Company::select('establishment_name')->whereColumn('companies.id', 'leave_types.company_id'))->orderBy('name')
            ->paginate($perPage = $this->perPage($request))->withQueryString();

        return view('attendance.leave.types', [
            'leaveTypes' => $leaveTypes,
            'perPage' => $perPage,
            'companies' => Company::with('parent')->orderBy('establishment_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LeaveType::create($this->validated($request));

        return back()->with('status', __('Leave type added.'));
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($this->validated($request, $leaveType));

        return back()->with('status', __('Leave type updated.'));
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->delete();

        return back()->with('status', __('Leave type deleted.'));
    }

    private function validated(Request $request, ?LeaveType $leaveType = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('leave_types')->where('company_id', $request->integer('company_id'))->ignore($leaveType)],
            'days' => ['required', 'numeric', 'min:0', 'max:366'],
            'is_paid' => ['nullable', 'boolean'],
        ], ['code.unique' => __('This company already has a leave type with that code.')], ['name' => 'leave type', 'code' => 'leave code', 'days' => 'leave days']);

        return ['is_paid' => $request->boolean('is_paid')] + $data;
    }
}
