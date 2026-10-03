<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ComplianceTask;
use App\Models\User;
use App\Services\Compliance\ComplianceTasks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Compliance › To Do List: the compliance tasks falling due, with stat
 * cards, filters, and complete / reopen / assign / delete, plus tasks added
 * by hand.
 */
class ComplianceTaskController extends Controller
{
    public function __construct(private ComplianceTasks $tasks) {}

    public function index(Request $request): View
    {
        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company'));
        foreach ($company ? [$company] : $companies as $c) {
            $this->tasks->refresh($c, $request->boolean('refresh'));
        }

        $all = ComplianceTask::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->with(['company.parent', 'employee', 'assignee', 'completer'])
            ->orderBy('due_on')->orderBy('id')->get();
        // Like the old page, the list opens on pending tasks; "all" shows completed ones too.
        $status = $request->query('status', 'pending');
        $tasks = $all
            ->when($status === 'pending', fn ($c) => $c->whereNull('completed_at'))
            ->when(! in_array($status, ['pending', 'all'], true), fn ($c) => $c->filter(fn (ComplianceTask $t) => $t->status() === $status))
            ->when($request->query('department'), fn ($c, $d) => $c->where('department', $d))
            ->when($request->query('type'), fn ($c, $type) => $c->where('type', $type));

        return view('compliance.tasks', [
            'companies' => $companies,
            'company' => $company,
            'status' => $status,
            'stats' => collect(['overdue', 'week', 'completed', 'upcoming'])->mapWithKeys(fn ($s) => [$s => $all->filter(fn ($t) => $t->status() === $s)->count()])->put('total', $all->count()),
            // Per-employee tasks of a kind are grouped under one row; the rest are listed singly.
            'groups' => $tasks->groupBy(fn (ComplianceTask $t) => $t->employee_id ? $t->type.'|'.$t->company_id : 'task:'.$t->id),
            'types' => collect(config('compliance.tasks'))->map(fn ($d) => $d['title'])->put('custom', __('Added by hand')),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company' => ['required', 'uuid', Rule::exists('companies', 'uuid')],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(config('compliance.task_categories'))],
            'department' => ['required', Rule::in(config('compliance.task_departments'))],
            'due_on' => ['required', 'date'],
            'priority' => ['required', Rule::in(array_keys(ComplianceTask::PRIORITIES))],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['due_on' => 'due date']);

        ComplianceTask::create(['company_id' => Company::where('uuid', $data['company'])->value('id'), 'type' => 'custom', 'period' => '']
            + collect($data)->except('company')->all());

        return back()->with('status', __('Task added.'));
    }

    public function update(Request $request, ComplianceTask $task): RedirectResponse
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
            'priority' => ['sometimes', Rule::in(array_keys(ComplianceTask::PRIORITIES))],
            'due_on' => ['sometimes', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);
        $task->update($data);

        return back()->with('status', __('Task updated.'));
    }

    public function complete(Request $request, ComplianceTask $task): RedirectResponse
    {
        $task->update(['completed_at' => now(), 'completed_by' => $request->user()->id]);

        return back()->with('status', __('Marked complete: :title', ['title' => $task->title]));
    }

    public function reopen(ComplianceTask $task): RedirectResponse
    {
        $task->update(['completed_at' => null, 'completed_by' => null]);

        return back()->with('status', __('Reopened: :title', ['title' => $task->title]));
    }

    public function destroy(ComplianceTask $task): RedirectResponse
    {
        $task->delete();

        return back()->with('status', __('Task deleted.'));
    }
}
