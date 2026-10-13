<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\CurrentCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The top-bar company picker. */
class CompanyContextController extends Controller
{
    /** Options, loaded when the picker is first opened. */
    public function index(): JsonResponse
    {
        $current = CurrentCompany::uuid();

        return response()->json(CurrentCompany::accessible()->orderBy('establishment_name')->get(['id', 'uuid', 'establishment_name', 'district'])
            ->map(fn (Company $c) => [
                'id' => $c->uuid,
                'name' => $c->establishment_name,
                'district' => $c->district,
                'initials' => mb_strtoupper(mb_substr($c->establishment_name, 0, 2)),
                'hue' => CurrentCompany::hue($c->id),
                'current' => $c->uuid === $current,
            ]));
    }

    public function update(Request $request): RedirectResponse
    {
        $uuid = $request->validate(['company' => ['nullable', 'string']])['company'] ?? null;

        if ($uuid !== null && ! CurrentCompany::accessible()->where('uuid', $uuid)->exists()) {
            return back()->with('error', __('You do not have access to this company.'));
        }

        CurrentCompany::set($uuid);

        return redirect()->to($this->previousWithCompany($uuid));
    }

    /** Back to the same page, showing the newly picked company (or each page's default for "All"). */
    private function previousWithCompany(?string $uuid): string
    {
        $previous = url()->previous();
        parse_str(parse_url($previous, PHP_URL_QUERY) ?? '', $query);
        unset($query['company'], $query['page']);
        if ($uuid) {
            $query['company'] = $uuid;
        }

        return strtok($previous, '?').($query ? '?'.http_build_query($query) : '');
    }
}
