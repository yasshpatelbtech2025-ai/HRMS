<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A medical examination of an employee by the certifying surgeon (Factory
 * Act Form 5, 27-A, 30 and 32).
 */
#[Fillable(['company_id', 'employee_id', 'examined_on', 'result', 'certifying_surgeon', 'signs_symptoms', 'extended_until', 'details', 'signed_copy_path', 'signed_copy_name'])]
class MedicalExamination extends Model
{
    use Auditable, BelongsToCompany, HasUuids, Trashable;

    /** The `details` fields, as label => input type, grouped as on the examination form. */
    public const DETAILS = [
        'Examination' => [
            'tests' => ['Nature of tests & results thereof', 'text'],
            'identification_marks' => ['Identification marks', 'text'],
            'physical_disability' => ['Physical disability, if any', 'text'],
            'process' => ['Manufacturing process / department proposed', 'text'],
        ],
        'Hazardous work (Form 32)' => [
            'hazardous_process' => ['Name of hazardous process', 'text'],
            'dangerous_operation' => ['Dangerous process / operation', 'text'],
            'raw_materials' => ['Raw materials, products or by-products exposed to', 'text'],
            'posted_on' => ['Date of posting', 'date'],
            'left_on' => ['Date of leaving / transfer', 'date'],
            'leaving_reason' => ['Reasons for discharge / leaving or transfer', 'text'],
        ],
        'If declared unfit' => [
            'withdrawal_period' => ['Period of temporary withdrawal from that work', 'text'],
            'withdrawal_reason' => ['Reasons for such withdrawal', 'text'],
            'unfit_on' => ['Date of declaring him unfit for that work', 'date'],
            'certificate_on' => ['Date of issuing fitness certificate', 'date'],
            'refusal_reason' => ['Reason for refusal / revocation of certificate', 'text'],
        ],
        'Lead work (Form 30)' => [
            'lead_symptoms' => ['Symptoms of lead poisoning (if any)', 'text'],
        ],
    ];

    protected function casts(): array
    {
        return ['examined_on' => 'date', 'extended_until' => 'date', 'details' => 'array'];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /** When the record is finally removed (30 days after deletion), so is the signed copy. */
    public function pruning(): void
    {
        if ($this->signed_copy_path) {
            Storage::disk(config('filesystems.employee_files'))->delete($this->signed_copy_path);
        }
    }

    public function detail(string $key): ?string
    {
        return $this->details[$key] ?? null;
    }
}
