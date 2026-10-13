<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Support\CurrentCompany;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CurrentCompany $current): View
    {
        $companyId = $current->id();
        $employees = fn () => Employee::active()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $monthStart = now()->startOfMonth();

        // Only numbers backed by real data. Modules not built yet are passed as null
        // and render as clearly marked placeholders.
        return view('dashboard', [
            'company' => $current->get(),
            'stats' => [
                'employees' => $employees()->count(),
                'companies' => $companyId ? null : Company::count(),
                'departments' => $companyId ? Department::where('company_id', $companyId)->count() : null,
                'joined' => $employees()->where('joining_date', '>=', $monthStart)->count(),
                'separated' => Employee::separated()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('separation_date', '>=', $monthStart)->count(),
            ],
            'missing' => [
                'aadhaar' => $employees()->whereNull('aadhaar_number')->count(),
                'uan' => $employees()->whereNull('uan_number')->count(),
                'bank' => $employees()->where(fn ($q) => $q->whereNull('bank_account_number')->orWhereNull('ifsc_code'))->count(),
            ],
        ]);
    }
}
