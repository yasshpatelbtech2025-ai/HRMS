<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Support\TableExport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attendance › Leave › Leaves: leave applications, approved by a manager
 * (possibly for different dates) or rejected.
 */
class LeaveController extends Controller
{
    use ListsRecords;

    public const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function index(Request $request): View
    {
        return view('attendance.leave.index', [
            'leaves' => $this->filtered($request)->paginate($perPage = $this->perPage($request))->withQueryString(),
            'perPage' => $perPage,
            'employees' => Employee::current()->orderBy('company_id')->orderBy('employee_code')->get(['id', 'uuid', 'company_id', 'employee_code', 'first_name', 'middle_name', 'last_name']),
            'leaveTypes' => LeaveType::orderBy('name')->get(['id', 'company_id', 'name', 'code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = Employee::where('uuid', $request->input('employee'))->first();

        $data = $request->validate([
            'employee' => ['required', 'uuid', Rule::exists('employees', 'uuid')->whereNull('deleted_at')],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('company_id', $employee?->company_id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days' => ['nullable', 'numeric', 'min:0.5', 'max:366'],
            'hard_copy' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [], ['leave_type_id' => 'leave type', 'start_date' => 'from date', 'end_date' => 'end date', 'days' => 'total days', 'hard_copy' => 'application hard copy']);

        $leave = LeaveApplication::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => $data['days'] ?? $this->daysBetween($data['start_date'], $data['end_date']),
            'reason' => $data['reason'] ?? null,
            'status' => LeaveApplication::PENDING,
        ]);

        if ($request->hasFile('hard_copy')) {
            $file = $request->file('hard_copy');
            $leave->update(['hard_copy_path' => Storage::disk(config('filesystems.employee_files'))
                ->putFileAs("leave-applications/{$leave->uuid}", $file, 'hard-copy-'.Str::random(8).'.'.$file->guessExtension())]);
        }

        return back()->with('status', __('Leave application added for :name.', ['name' => $employee->full_name]));
    }

    public function approve(Request $request, LeaveApplication $leave): RedirectResponse
    {
        $data = $request->validate([
            'approved_start_date' => ['required', 'date'],
            'approved_end_date' => ['required', 'date', 'after_or_equal:approved_start_date'],
            'approved_days' => ['nullable', 'numeric', 'min:0.5', 'max:366'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [], ['approved_start_date' => 'from date', 'approved_end_date' => 'end date', 'approved_days' => 'total days']);

        $overlap = LeaveApplication::where('employee_id', $leave->employee_id)->whereKeyNot($leave->id)
            ->approvedBetween($data['approved_start_date'], $data['approved_end_date'])->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['approved_start_date' => __(':name already has approved leave in these dates.', ['name' => $leave->employee->full_name])]);
        }

        $leave->update([
            'status' => LeaveApplication::APPROVED,
            'approved_start_date' => $data['approved_start_date'],
            'approved_end_date' => $data['approved_end_date'],
            'approved_days' => $data['approved_days'] ?? $this->daysBetween($data['approved_start_date'], $data['approved_end_date']),
            'approval_description' => $data['description'] ?? null,
            'approved_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return back()->with('status', __('Leave for :name approved.', ['name' => $leave->employee->full_name]));
    }

    public function reject(Request $request, LeaveApplication $leave): RedirectResponse
    {
        $leave->update([
            'status' => LeaveApplication::REJECTED, 'approved_start_date' => null, 'approved_end_date' => null, 'approved_days' => null,
            'approved_by' => $request->user()->id, 'decided_at' => now(),
        ]);

        return back()->with('status', __('Leave for :name rejected.', ['name' => $leave->employee->full_name]));
    }

    public function destroy(LeaveApplication $leave): RedirectResponse
    {
        if ($leave->hard_copy_path) {
            Storage::disk(config('filesystems.employee_files'))->delete($leave->hard_copy_path);
        }
        $leave->delete();

        return $this->deleted($leave, __('Leave application deleted.'));
    }

    public function hardCopy(LeaveApplication $leave): StreamedResponse
    {
        $disk = Storage::disk(config('filesystems.employee_files'));
        abort_unless($leave->hard_copy_path && $disk->exists($leave->hard_copy_path), 404);

        return $disk->response($leave->hard_copy_path);
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        return TableExport::download($format, 'leave-applications',
            ['Sl', 'Employee name', 'Type', 'Apply date', 'Leave start date', 'Leave end date', 'Days', 'Is paid', 'Status'],
            $this->filtered($request)->get()->map(fn (LeaveApplication $l, int $i) => [
                $i + 1, $l->employee->full_name, $l->leaveType->code, $l->created_at->toDateString(),
                ($l->approved_start_date ?? $l->start_date)->toDateString(), ($l->approved_end_date ?? $l->end_date)->toDateString(),
                $l->approved_days ?? $l->days, $l->leaveType->is_paid ? 'Yes' : 'No', ucfirst($l->status),
            ]),
        );
    }

    private function filtered(Request $request): Builder
    {
        return LeaveApplication::with(['employee', 'leaveType'])
            ->when(array_key_exists((string) $request->query('status'), self::STATUSES), fn ($q) => $q->where('status', $request->query('status')))
            ->tap(fn (Builder $q) => $this->search($q, $request, ['employee.first_name', 'employee.last_name', 'employee.employee_code', 'leaveType.name', 'leaveType.code']))
            ->latest();
    }

    private function daysBetween(string $from, string $to): int
    {
        return (int) CarbonImmutable::parse($from)->diffInDays($to) + 1;
    }
}
