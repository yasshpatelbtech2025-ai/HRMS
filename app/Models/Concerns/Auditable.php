<?php

namespace App\Models\Concerns;

use App\Models\Employee;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Model;

/**
 * Logs every create, change, delete and restore of the model to Configs ›
 * Activity log. Passwords, tokens and encrypted fields (Aadhaar, PAN, bank
 * account...) are recorded only as "changed", never their values.
 */
trait Auditable
{
    /** Bookkeeping columns not worth an entry of their own. */
    private static array $auditIgnored = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'last_login_at'];

    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            if (! Activity::muted()) {
                Activity::record('created', $model, $model->auditLabel(), ['new' => $model->auditValues(array_filter($model->getAttributes(), fn ($v) => $v !== null && $v !== ''))]);
            }
        });

        static::updated(function (Model $model) {
            $changed = array_diff_key($model->getChanges(), array_flip(self::$auditIgnored));
            if ($changed && ! Activity::muted()) {
                Activity::record('updated', $model, $model->auditLabel(), [
                    'old' => $model->auditValues(array_intersect_key($model->getPrevious(), $changed)),
                    'new' => $model->auditValues($changed),
                ]);
            }
        });

        static::deleted(function (Model $model) {
            if (! Activity::muted()) {
                $forever = ! method_exists($model, 'isForceDeleting') || $model->isForceDeleting();
                Activity::record($forever ? 'purged' : 'deleted', $model, $model->auditLabel());
            }
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                if (! Activity::muted()) {
                    Activity::record('restored', $model, $model->auditLabel());
                }
            });
        }
    }

    /**
     * A readable name for the log: the record's own name or title, or the
     * employee it belongs to.
     */
    public function auditLabel(): ?string
    {
        foreach (['full_name', 'establishment_name', 'name', 'title'] as $attribute) {
            if (filled($value = $this->getAttribute($attribute))) {
                return (string) $value;
            }
        }

        if ($this->getAttribute('employee_id')) {
            return Employee::withoutGlobalScopes()->whereKey($this->getAttribute('employee_id'))->first()?->full_name;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $values  raw attribute values
     * @return array<string, mixed>
     */
    protected function auditValues(array $values): array
    {
        $secret = array_merge($this->getHidden(), array_keys(array_filter($this->getCasts(), fn ($cast) => str_starts_with((string) $cast, 'encrypted') || $cast === 'hashed')));

        return collect($values)->except(self::$auditIgnored)
            ->map(fn ($value, $key) => in_array($key, $secret, true) ? '••• hidden' : (is_string($value) ? mb_substr($value, 0, 500) : $value))
            ->all();
    }
}
