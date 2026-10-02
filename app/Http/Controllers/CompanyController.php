<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::with('parent')
            ->when(request('search'), fn ($query, $term) => $query->where('establishment_name', 'like', "%{$term}%"))
            ->orderBy('establishment_name')
            ->paginate(15)
            ->withQueryString();

        return view('companies.index', compact('companies'));
    }

    public function create(): View
    {
        return view('companies.create', [
            'company' => new Company,
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

        return redirect()->route('company.index')->with('status', __('Company deleted.'));
    }
}
