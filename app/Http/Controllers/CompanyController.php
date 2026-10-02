<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    public const PER_PAGE = [10, 25, 50, 100];

    /**
     * Offered in the State dropdown alongside any state already in use.
     */
    public const STATES = [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat',
        'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh',
        'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh',
        'Uttarakhand', 'West Bengal', 'Andaman and Nicobar Islands', 'Chandigarh',
        'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir', 'Ladakh',
        'Lakshadweep', 'Puducherry',
    ];

    public function index(Request $request): View
    {
        $perPage = in_array($request->integer('per_page'), self::PER_PAGE) ? $request->integer('per_page') : self::PER_PAGE[0];

        $companies = $this->filtered($request)->paginate($perPage)->withQueryString();

        return view('companies.index', [
            'companies' => $companies,
            'perPage' => $perPage,
            'parents' => Company::orderBy('establishment_name')->get(['id', 'establishment_name']),
            'states' => $this->choices('state', self::STATES),
            'districts' => $this->choices('district'),
            'talukas' => $this->choices('taluka'),
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated());

        return back()->with('status', __('Company added.'));
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        return back()->with('status', __('Company updated.'));
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->children()->exists()) {
            return back()->with('error', __('This company has connected companies. Reassign or delete them first.'));
        }

        if ($company->employees()->exists()) {
            return back()->with('error', __('This company has employees. Move or remove them first.'));
        }

        $company->delete();

        return back()->with('status', __('Company deleted.'));
    }

    public function export(Request $request): StreamedResponse
    {
        $companies = $this->filtered($request)->get();

        return response()->streamDownload(function () use ($companies) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Sl', 'Establishment Name', 'Organization size', 'Connected company', 'Address Line 1', 'Address Line 2', 'Village', 'State', 'District', 'Taluka']);

            foreach ($companies as $i => $company) {
                fputcsv($out, [
                    $i + 1, $company->establishment_name, $company->employees_count, $company->parent?->establishment_name,
                    $company->address_line_1, $company->address_line_2, $company->village, $company->state, $company->district, $company->taluka,
                ]);
            }

            fclose($out);
        }, 'companies.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Companies matching the list's search and sort, with organization size
     * (the number of employees) and connected company loaded.
     */
    private function filtered(Request $request): Builder
    {
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        return Company::with('parent')
            ->withCount('employees')
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_strtolower(trim($request->query('search')))).'%';
                $query->where(fn (Builder $q) => $q
                    ->whereRaw('lower(establishment_name) like ?', [$like])
                    ->orWhereHas('parent', fn (Builder $p) => $p->whereRaw('lower(establishment_name) like ?', [$like])));
            })
            ->when(
                $request->query('sort') === 'size',
                fn (Builder $q) => $q->orderBy('employees_count', $direction)->orderBy('establishment_name'),
                fn (Builder $q) => $q->orderBy('establishment_name', $direction),
            );
    }

    /**
     * Values for a dropdown that also accepts new entries: the defaults plus
     * every distinct value already saved on a company.
     */
    private function choices(string $column, array $defaults = []): array
    {
        return collect($defaults)
            ->merge(Company::whereNotNull($column)->distinct()->pluck($column))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
