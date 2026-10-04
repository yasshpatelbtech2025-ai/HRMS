<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Requests\PositionRequest;
use App\Models\Company;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Employee › Positions tab: the designations available to each company.
 */
class PositionController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = Position::with('company.parent')
            ->tap(fn (Builder $q) => $this->search($q, $request, ['name', 'details', 'company.establishment_name']));

        $query = match ($request->query('sort')) {
            'name' => $query->orderBy('name', $direction),
            'status' => $query->orderBy('is_active', $direction === 'asc' ? 'desc' : 'asc')->orderBy('name'),
            default => $query->orderBy(Company::select('establishment_name')->whereColumn('companies.id', 'positions.company_id'), $direction)->orderBy('name'),
        };

        return view('employees.positions.index', [
            'positions' => $query->paginate($perPage = $this->perPage($request))->withQueryString(),
            'perPage' => $perPage,
            'companies' => Company::with('parent')->orderBy('establishment_name')->get(),
        ]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        Position::create($request->validated());

        return back()->with('status', __('Position added.'));
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        return back()->with('status', __('Position updated.'));
    }

    public function destroy(Position $position): RedirectResponse
    {
        $position->delete();

        return $this->deleted($position, __('Position deleted.'));
    }
}
