<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Requests\PositionRequest;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PositionController extends Controller
{
    use ListsRecords;

    public function index(): View
    {
        $query = Position::withCount(['employees' => fn ($q) => $q->active()])
            ->when(request('search'), fn ($query, $term) => $query->where('name', 'like', "%{$term}%"));

        return view('positions.index', [
            'positions' => $this->sorted($query, ['name' => 'name', 'employees' => 'employees_count'], 'name')
                ->paginate($this->perPage())->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('positions.form', ['position' => new Position]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        Position::create($request->validated());

        return redirect()->route('positions.index')->with('status', __('Designation added.'));
    }

    public function edit(Position $position): View
    {
        return view('positions.form', compact('position'));
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        return redirect()->route('positions.index')->with('status', __('Designation updated.'));
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->employees()->exists()) {
            return back()->with('error', __('Employees still hold this designation. Move them to another one first.'));
        }

        $position->delete();

        return redirect()->route('positions.index')->with('status', __('Designation deleted.'))
            ->with('undo', route('positions.restore', $position));
    }

    public function restore(Position $position): RedirectResponse
    {
        $position->restore();

        return back()->with('status', __('Designation restored.'));
    }
}
