<?php

namespace App\Rules;

use App\Models\Company;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The company exists and the signed-in user has access to it (admins: any).
 * Rule::exists alone checks the table directly, past the company access scope.
 */
class AccessibleCompany implements ValidationRule
{
    public function __construct(private string $column = 'id') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value) || ! Company::where($this->column, $value)->exists()) {
            $fail(__('You do not have access to this company.'));
        }
    }
}
