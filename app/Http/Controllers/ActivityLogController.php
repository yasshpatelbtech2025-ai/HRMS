<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configs › Activity log (admins only): who did what and when, newest first,
 * filtered by person, company, kind of activity and dates.
 */
class ActivityLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'user' => ['nullable', 'integer'],
            'company' => ['nullable', 'integer'],
            'event' => ['nullable', 'string', 'in:'.implode(',', array_keys(ActivityLog::EVENTS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $entries = ActivityLog::with(['user:id,name', 'company:id,establishment_name'])
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['company'] ?? null, fn ($q, $id) => $q->where('company_id', $id))
            ->when($filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->whereRaw('lower(description) like ?', ['%'.mb_strtolower($term).'%']))
            ->latest('created_at')->latest('id')
            ->paginate(50)->withQueryString();

        return view('configs.activity-log', [
            'entries' => $entries,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'companies' => Company::withTrashed()->orderBy('establishment_name')->get(['id', 'establishment_name']),
            'filters' => $filters,
        ]);
    }
}
