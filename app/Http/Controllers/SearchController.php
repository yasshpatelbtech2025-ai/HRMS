<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Feeds the command palette. */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['employees' => [], 'companies' => []]);
        }

        $employees = Employee::with('company:id,establishment_name')
            ->where(fn ($q) => $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('employee_code', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%"))
            ->orderBy('first_name')->limit(5)->get()
            ->map(fn (Employee $e) => [
                'label' => $e->full_name,
                'section' => $e->employee_code.' · '.$e->company->establishment_name.($e->status === Employee::SEPARATED ? ' · '.__('Separated') : ''),
                'url' => route('employees.edit', $e),
            ]);

        $companies = Company::where('establishment_name', 'like', "%{$term}%")
            ->orderBy('establishment_name')->limit(5)->get()
            ->map(fn (Company $c) => [
                'label' => $c->establishment_name,
                'section' => collect([$c->district, $c->state])->filter()->implode(', ') ?: null,
                'url' => route('company.edit', $c),
            ]);

        return response()->json(['employees' => $employees, 'companies' => $companies]);
    }
}
