<?php

namespace App\Rules;

use App\Models\Employee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The employee is in a company the signed-in user has access to (admins:
 * any). Rule::exists alone checks the table directly, past the company
 * access scope.
 */
class AccessibleEmployee implements ValidationRule
{
    public function __construct(private string $column = 'uuid') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value) || ! Employee::withTrashed()->where($this->column, $value)->exists()) {
            $fail(__('You do not have access to this employee.'));
        }
    }
}
