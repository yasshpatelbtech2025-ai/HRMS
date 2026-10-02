<?php

namespace App\Http\Controllers;

use App\Http\Requests\PositionRequest;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(): View
    {
        return view('positions.index', [
            'positions' => Position::withCount('employees')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('positions.form', ['position' => new Position]);
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        Position::create($request->validated());

        return redirect()->route('positions.index')->with('status', __('Position added.'));
    }

    public function edit(Position $position): View
    {
        return view('positions.form', compact('position'));
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        return redirect()->route('positions.index')->with('status', __('Position updated.'));
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->employees()->exists()) {
            return back()->with('error', __('Employees still hold this position.'));
        }

        $position->delete();

        return redirect()->route('positions.index')->with('status', __('Position deleted.'));
    }
}
