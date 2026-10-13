<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    use ListsRecords;

    public function index(): View
    {
        $query = Company::with('parent')
            ->withCount(['employees' => fn ($q) => $q->active(), 'departments'])
            ->when(request('search'), fn ($query, $term) => $query->where('establishment_name', 'like', "%{$term}%"));

        $companies = $this->sorted($query, [
            'name' => 'establishment_name',
            'employees' => 'employees_count',
            'size' => 'organization_size',
            'district' => 'district',
        ], 'name')->paginate($this->perPage())->withQueryString();

        return view('companies.index', compact('companies'));
    }

    public function create(): View
    {
        return view('companies.create', [
            'company' => new Company(['state' => 'Gujarat']),
            'parents' => Company::orderBy('establishment_name')->get(['id', 'establishment_name']),
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated());

        return redirect()->route('company.index')->with('status', __('Company added.'));
    }

    public function edit(Company $company): View
    {
        return view('companies.edit', [
            'company' => $company,
            'parents' => Company::whereKeyNot($company->id)->orderBy('establishment_name')->get(['id', 'establishment_name']),
        ]);
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        return redirect()->route('company.index')->with('status', __('Company updated.'));
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->children()->exists()) {
            return back()->with('error', __('This company has connected companies. Reassign or delete them first.'));
        }

        $company->delete();

        return redirect()->route('company.index')
            ->with('status', __(':name deleted.', ['name' => $company->establishment_name]))
            ->with('undo', route('company.restore', $company));
    }

    public function restore(Company $company): RedirectResponse
    {
        $company->restore();

        return back()->with('status', __(':name restored.', ['name' => $company->establishment_name]));
    }
}
