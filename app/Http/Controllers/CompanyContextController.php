<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\CurrentCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyContextController extends Controller
{
    public function __invoke(Request $request, CurrentCompany $current): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $current->set(isset($data['company_id']) ? Company::find($data['company_id']) : null);

        return redirect()->to($this->previousWithoutCompanyFilters());
    }

    /** Options for the top-bar switcher, loaded when it is first opened. */
    public function index(CurrentCompany $current): JsonResponse
    {
        return response()->json(Company::orderBy('establishment_name')->get(['id', 'establishment_name', 'district'])
            ->map(fn (Company $c) => [
                'id' => $c->id,
                'name' => $c->establishment_name,
                'district' => $c->district,
                'initials' => mb_strtoupper(mb_substr($c->establishment_name, 0, 2)),
                'hue' => CurrentCompany::hue($c->id),
                'current' => $c->id === $current->id(),
            ]));
    }

    /** Go back to the same page, dropping filters that belonged to the old company. */
    private function previousWithoutCompanyFilters(): string
    {
        $previous = url()->previous();
        $parts = parse_url($previous);
        parse_str($parts['query'] ?? '', $query);
        unset($query['company_id'], $query['department_id'], $query['page']);

        return strtok($previous, '?').($query ? '?'.http_build_query($query) : '');
    }
}
