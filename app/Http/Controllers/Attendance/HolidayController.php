<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Attendance › Leave › Holiday list: the master list of holidays that
 * companies choose from on the Holiday tab.
 */
class HolidayController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        $year = $this->year($request);

        $holidays = Holiday::inYear($year)->withCount('companies')
            ->tap(fn ($q) => $this->search($q, $request, ['name']))
            ->tap(fn ($q) => $this->sort($q, $request, ['name' => 'name', 'from' => 'start_date', 'to' => 'end_date'], fn ($q) => $q->orderBy('start_date')))
            ->paginate($perPage = $this->perPage($request))->withQueryString();

        return view('attendance.leave.holiday-list', compact('holidays', 'perPage', 'year'));
    }

    public function store(Request $request): RedirectResponse
    {
        Holiday::create($this->validated($request));

        return back()->with('status', __('Holiday added.'));
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($this->validated($request));

        return back()->with('status', __('Holiday updated.'));
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        if ($holiday->companies()->exists()) {
            return back()->with('error', __(':name is observed by :count company(s). Remove it from them on the Holiday tab first.', [
                'name' => $holiday->name, 'count' => $holiday->companies()->count(),
            ]));
        }

        $holiday->delete();

        return $this->deleted($holiday, __('Holiday deleted.'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [], ['name' => 'holiday name', 'start_date' => 'from date', 'end_date' => 'to date']);
    }

    private function year(Request $request): int
    {
        $year = $request->integer('year', (int) date('Y'));

        return $year >= 2000 && $year <= 2100 ? $year : (int) date('Y');
    }
}
