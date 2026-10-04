<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file uploaded to a document slot on the Documents page.
 */
#[Fillable(['slot', 'category', 'company_id', 'path', 'original_name', 'mime_type', 'size', 'uploaded_by'])]
class DocumentFile extends Model
{
    use Auditable, BelongsToCompany, HasUuids, Trashable;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** When the record is finally removed (30 days after deletion), so is the file. */
    public function pruning(): void
    {
        Storage::disk(config('filesystems.employee_files'))->delete($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
