<?php

namespace App\Support;

use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\ComplianceRegisterEntry;
use App\Models\ComplianceTask;
use App\Models\Concerns\Trashable;
use App\Models\DocumentFile;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Loan;
use App\Models\MedicalExamination;
use App\Models\PayrollRule;
use App\Models\Position;
use App\Models\SalaryAdjustment;
use App\Models\SalaryAdvance;
use App\Models\SubDepartment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The records that are soft-deleted (Configs › Recently deleted): how each
 * is named in the list and on the Undo of its "deleted" toast.
 */
class RecentlyDeleted
{
    /** Days a deleted record can be restored before it is pruned (App\Models\Concerns\Trashable). */
    public const RETENTION_DAYS = 30;

    /** type key => [model, label] */
    public const TYPES = [
        'employee' => [Employee::class, 'Employee'],
        'company' => [Company::class, 'Company'],
        'sub-department' => [SubDepartment::class, 'Sub department'],
        'position' => [Position::class, 'Position'],
        'leave-type' => [LeaveType::class, 'Leave type'],
        'holiday' => [Holiday::class, 'Holiday'],
        'leave-application' => [LeaveApplication::class, 'Leave application'],
        'attendance-request' => [AttendanceRequest::class, 'Attendance request'],
        'loan' => [Loan::class, 'Loan'],
        'salary-advance' => [SalaryAdvance::class, 'Salary advance'],
        'salary-adjustment' => [SalaryAdjustment::class, 'Salary adjustment'],
        'payroll-rule' => [PayrollRule::class, 'Payroll rule'],
        'document-file' => [DocumentFile::class, 'Document'],
        'medical-examination' => [MedicalExamination::class, 'Medical examination'],
        'register-entry' => [ComplianceRegisterEntry::class, 'Register row'],
        'compliance-task' => [ComplianceTask::class, 'Compliance task'],
    ];

    public static function type(Model $model): ?string
    {
        foreach (self::TYPES as $type => [$class]) {
            if ($model instanceof $class) {
                return $type;
            }
        }

        return null;
    }

    /** Whether the type is removed for good after RETENTION_DAYS (employees and companies are kept). */
    public static function expires(string $type): bool
    {
        return in_array(Trashable::class, class_uses_recursive(self::TYPES[$type][0]), true);
    }

    public static function restoreUrl(Model $model): ?string
    {
        $type = self::type($model);

        return $type ? route('recently-deleted.restore', [$type, $model->getKey()]) : null;
    }

    /**
     * What the record is, and where it belongs.
     *
     * @return array{title: string, context: string}
     */
    public static function describe(Model $model): array
    {
        $title = match (true) {
            $model instanceof LeaveApplication => trim(($model->leaveType?->name ?? __('Leave')).' '.$model->start_date?->format('d M Y').($model->end_date && ! $model->end_date->eq($model->start_date) ? ' – '.$model->end_date->format('d M Y') : '')),
            $model instanceof AttendanceRequest => __('Attendance request for :date', ['date' => $model->work_date?->format('d M Y')]),
            $model instanceof SalaryAdvance => __('Advance of :amount', ['amount' => number_format((float) $model->amount, 2)]),
            $model instanceof MedicalExamination => __('Examination on :date', ['date' => $model->examined_on?->format('d M Y')]),
            $model instanceof ComplianceRegisterEntry => __(':form row for :month', ['form' => strtoupper($model->form), 'month' => $model->month?->format('M Y')]),
            $model instanceof Holiday => trim($model->name.' · '.$model->start_date?->format('d M Y')),
            default => (string) ($model->full_name ?? $model->establishment_name ?? $model->title ?? $model->original_name ?? $model->loan_no ?? $model->name ?? '#'.$model->getKey()),
        };

        $employee = method_exists($model, 'employee') ? $model->employee : null;
        $company = method_exists($model, 'company') ? $model->company : $employee?->company;

        return [
            'title' => $title,
            'context' => collect([$model instanceof Employee ? null : $employee?->full_name, $model instanceof Company ? null : $company?->establishment_name])->filter()->implode(' · '),
        ];
    }

    /**
     * Everything deleted, newest first.
     *
     * @return Collection<int, array{type: string, label: string, model: Model, title: string, context: string, expires: bool}>
     */
    public static function all(?string $only = null, int $limit = 300): Collection
    {
        return collect(self::TYPES)
            ->when($only, fn ($types) => $types->only([$only]))
            ->flatMap(fn ($entry, $type) => $entry[0]::onlyTrashed()->latest('deleted_at')->limit($limit)->get()
                ->map(fn (Model $m) => ['type' => $type, 'label' => $entry[1], 'model' => $m, 'expires' => self::expires($type)] + self::describe($m)))
            ->sortByDesc(fn ($row) => $row['model']->deleted_at)
            ->take($limit)
            ->values();
    }
}
